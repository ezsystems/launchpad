<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Command\Docker;

use eZ\Launchpad\Console\Application;
use eZ\Launchpad\Core\Client\Docker;
use eZ\Launchpad\Core\Command;
use eZ\Launchpad\Core\DockerCompose;
use eZ\Launchpad\Core\ProcessRunner;
use eZ\Launchpad\Core\ProjectStatusDumper;
use eZ\Launchpad\Core\ProjectWizard;
use eZ\Launchpad\Core\TaskExecutor;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class Initialize.
 */
class Initialize extends Command
{
    /**
     * @var ProjectStatusDumper
     */
    protected $projectStatusDumper;

    /**
     * Status constructor.
     *
     * @param ProjectStatusDumper $projectStatusDumper
     */
    public function __construct(ProjectStatusDumper $projectStatusDumper)
    {
        parent::__construct();
        $this->projectStatusDumper = $projectStatusDumper;
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $this->projectStatusDumper->setIo($this->io);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('docker:initialize')->setDescription('Initialize the project and all the services.');
        $this->setAliases(['docker:init', 'initialize', 'init']);
        $this->addArgument('repository', InputArgument::OPTIONAL, 'eZ Platform Repository', 'ezsystems/ezplatform');
        $this->addArgument('version', InputArgument::OPTIONAL, 'eZ Platform Version', '1.12.*');
        $this->addArgument('initialdata', InputArgument::OPTIONAL, 'eZ Platform Initial', 'clean');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fs = new Filesystem();
        // get the boiler plate
        // run it with few mount
        $application = $this->getApplication();
        /* @var Application $application */
        $output->writeln($application->getLogo());

        // Get the Payload docker-compose.yml
        $compose = new DockerCompose("{$this->getPayloadDir()}/dev/docker-compose.yml");

        $wizard = new ProjectWizard($this->io, $this->projectConfiguration);

        // Ask the questions
        list(
            $networkName,
            $networkPort,
            $httpBasics,
            $selectedServices,
            $provisioningName,
            $composeFileName
            ) = $wizard(
            $compose
        );

        $compose->filterServices($selectedServices);

        // start the scafolding of the Payload
        $provisioningFolder = "{$this->projectPath}/{$provisioningName}";
        $fs->mkdir("{$provisioningFolder}/dev");
        $fs->mirror("{$this->getPayloadDir()}/dev", "{$provisioningFolder}/dev");
        $fs->chmod(
            [
                "{$provisioningFolder}/dev/nginx/entrypoint.bash",
                "{$provisioningFolder}/dev/engine/entrypoint.bash",
                "{$provisioningFolder}/dev/solr/entrypoint.bash",
            ],
            0755
        );

        // PHP.ini ADAPTATION
        $phpINIPath = "{$provisioningFolder}/dev/engine/php.ini";
        $conf       = <<<END
; memcache configuration in dev 
session.save_handler = memcached
session.save_path = "memcache:11211?persistent=1&weight=1&timeout=1&retry_interval=15"
END;
        $iniContent = file_get_contents($phpINIPath);
        $iniContent = str_replace(
            '##MEMCACHE_CONFIG##',
            in_array('memcache', $selectedServices) ? $conf : '',
            $iniContent
        );

        $conf       = <<<END
; mailcatcher configuration in dev 
sendmail_path = /usr/bin/env catchmail --smtp-ip mailcatcher --smtp-port 1025 -f docker@localhost
END;
        $iniContent = str_replace(
            '##SENDMAIL_CONFIG##',
            in_array('mailcatcher', $selectedServices) ? $conf : '',
            $iniContent
        );
        $fs->dumpFile($phpINIPath, $iniContent);
        unset($selectedServices);

        // Get the Payload README.md
        $fs->copy("{$this->getPayloadDir()}/README.md", "{$provisioningFolder}/README.md");

        $finalCompose = clone $compose;
        $compose->cleanForInitialize();
        // dump the temporary DockerCompose.yml without the mount and env vars in the provisioning folder
        $compose->dump("{$provisioningFolder}/dev/{$composeFileName}");

        // create the local configurations
        $localConfigurations = [
            'provisioning.folder_name'   => $provisioningName,
            'docker.compose_filename'    => $composeFileName,
            'docker.network_name'        => $networkName,
            'docker.network_prefix_port' => $networkPort,
        ];

        foreach ($httpBasics as $name => $httpBasic) {
            list($host, $user, $pass)                                    = $httpBasic;
            $localConfigurations["composer.http_basic.{$name}.host"]     = $host;
            $localConfigurations["composer.http_basic.{$name}.login"]    = $user;
            $localConfigurations["composer.http_basic.{$name}.password"] = $pass;
        }

        $this->projectConfiguration->setMultiLocal($localConfigurations);

        // Do the real installation
        $options      = [
            'compose-file'             => "{$provisioningFolder}/dev/{$composeFileName}",
            'network-name'             => $networkName,
            'network-prefix-port'      => $networkPort,
            'project-path'             => $this->projectPath,
            'provisioning-folder-name' => $provisioningName,
            'host-machine-mapping'     => $this->projectConfiguration->get('docker.host_machine_mapping'),
            'composer-cache-dir'       => $this->projectConfiguration->get('docker.host_composer_cache_dir'),
        ];
        $dockerClient = new Docker($options, new ProcessRunner());
        $this->projectStatusDumper->setDockerClient($dockerClient);
        $dockerClient->build(['--no-cache']);
        $dockerClient->up(['-d']);

        $executor = new TaskExecutor($dockerClient, $this->projectConfiguration, $this->requiredRecipes);
        $executor->composerInstall();

        // Fix #7
        // if eZ EE is selected then the DB is not selected by the install process
        // we have to do it manually here
        $repository  = $input->getArgument('repository');
        $initialdata = $input->getArgument('initialdata');

        if ('clean' === $initialdata && false !== strpos($repository, 'ezplatform-ee')) {
            $initialdata = 'studio-clean';
        }

        $executor->eZInstall($input->getArgument('version'), $repository, $initialdata);
        if ($finalCompose->hasService('solr')) {
            $executor->eZInstallSolr();
        }
        $finalCompose->removeUselessEnvironmentsVariables();

        $finalCompose->dump("{$provisioningFolder}/dev/{$composeFileName}");
        $dockerClient->up(['-d']);
        $executor->composerInstall();

        if ($finalCompose->hasService('solr')) {
            $executor->createCore();
            $executor->indexSolr();
        }
        $this->projectConfiguration->setEnvironment('dev');
        $this->projectStatusDumper->dump('ncsi');
    }
}
