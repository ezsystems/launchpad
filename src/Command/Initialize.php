<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Command;

use eZ\Launchpad\Console\Application;
use eZ\Launchpad\Core\Client\Docker;
use eZ\Launchpad\Core\Command;
use eZ\Launchpad\Core\ProjectWizard;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Initialize.
 */
class Initialize extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('docker:initialize')->setDescription('Initialize the project and all the services.');
        $this->setAliases(['docker:init', 'initialize', 'init']);
        $this->addArgument('repository', InputArgument::OPTIONAL, 'eZ Platform Repository', 'ezsystems/ezplatform');
        $this->addArgument('version', InputArgument::OPTIONAL, 'eZ Platform Version', '');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // get the boiler plate
        // run it with few mount
        $application = $this->getApplication();
        /* @var Application $application */
        $output->writeln($application->getLogo());

        $payloadFolder = "{$this->appDir}payload";
        // Get the Payload docker-compose.yml
        $compose = Yaml::parse(file_get_contents("{$payloadFolder}/dev/docker-compose.yml"));
        $wizard  = new ProjectWizard($this->io, $this->projectConfiguration);

        list(
            $networkName,
            $httpBasics,
            $networkPort,
            $selectedServices,
            $provisioningName,
            $composeFileName
            ) = $wizard(
            $compose
        );

        $provisioningFolder = "{$this->projectPath}/{$provisioningName}";

        // remove the mount on eZ Plaftorm as it is not installed yet.
        $selectedServicesFirstRun = $selectedServices;
        foreach ($selectedServicesFirstRun as $name => $service) {
            if (isset($service['volumes'])) {
                $volumes            = NovaCollection($service['volumes']);
                $service['volumes'] = $volumes->prune(
                    function ($value) {
                        return !preg_match('/ezplatform/', $value);
                    }
                )->toArray();
            }
            $selectedServicesFirstRun[$name] = $service;
        }

        // start the scafolding of the Payload
        $fs = new Filesystem();
        $fs->mkdir("{$provisioningFolder}/dev");
        $fs->mirror("{$payloadFolder}/dev", "{$provisioningFolder}/dev");
        $fs->chmod(
            [
                "{$provisioningFolder}/dev/nginx/entrypoint.bash",
                "{$provisioningFolder}/dev/engine/entrypoint.bash",
            ],
            0755
        );

        $recipes = ['composer_install.bash', 'ez_install.bash'];

        foreach ($recipes as $recipe) {
            $fs->copy("{$payloadFolder}/recipes/{$recipe}", "{$this->projectPath}/{$recipe}", true);
            $fs->chmod("{$this->projectPath}/recipes/{$recipe}", 0755);
        }

        // dump the temporary DockerCompose.yml without the mount on eZ Platform in the provisioning folder
        $this->dumpCompose($compose, $selectedServicesFirstRun, "{$provisioningFolder}/dev/{$composeFileName}");

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
        $dockerClient = new Docker($options);
        $dockerClient->build(['--no-cache']);
        $dockerClient->up(['-d']);

        // Composer Install
        $dockerClient->exec(
            "/var/www/html/project/{$recipes[0]}",
            ['--user', 'www-data'],
            'engine'
        );

        // Composer Configuration
        foreach ($this->projectConfiguration->get('composer.http_basic') as $auth) {
            if (!isset($auth['host']) || !isset($auth['login']) || !isset($auth['password'])) {
                continue;
            }
            $dockerClient->exec(
                '/var/www/html/project/composer.phar config --global'.
                " http-basic.{$auth['host']} {$auth['login']} {$auth['password']}",
                ['--user', 'www-data'],
                'engine'
            );
        }

        // eZ Install
        $version    = $input->getArgument('version');
        $repository = $input->getArgument('repository');
        $dockerClient->exec(
            "/var/www/html/project/{$recipes[1]} {$repository} {$version}",
            ['--user', 'www-data'],
            'engine'
        );

        // rebuild with mount after eZ install
        $this->dumpCompose($compose, $selectedServices, "{$provisioningFolder}/dev/{$composeFileName}");
        $dockerClient->build(['--no-cache']);
        $dockerClient->up(['-d']);

        foreach ($recipes as $recipe) {
            $fs->remove("{$this->projectPath}/{$recipe}");
        }
    }

    /**
     * @param array  $compose
     * @param array  $services
     * @param string $destination
     */
    protected function dumpCompose($compose, $services, $destination)
    {
        $compose['services'] = $services;
        $yaml                = Yaml::dump($compose);
        $fs                  = new Filesystem();
        $fs->dumpFile($destination, $yaml);
    }
}
