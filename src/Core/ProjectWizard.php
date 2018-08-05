<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Core;

use eZ\Launchpad\Configuration\Project as ProjectConfiguration;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class ProjectWizard
{
    /**
     * @var SymfonyStyle
     */
    protected $io;

    /**
     * @var ProjectConfiguration
     */
    protected $projectConfiguration;

    const INIT_STD               = 'standard';
    const INIT_STD_COMPOSER_AUTH = 'standard-with-composer-auth';
    const INIT_EXPERT            = 'expert';

    /**
     * @var array
     */
    protected static $modes = [
        self::INIT_STD,
        self::INIT_STD_COMPOSER_AUTH,
        self::INIT_EXPERT,
    ];

    /**
     * @var string
     */
    protected $mode;

    /**
     * ProjectWizard constructor.
     *
     * @param SymfonyStyle         $io
     * @param ProjectConfiguration $configuration
     */
    public function __construct(SymfonyStyle $io, ProjectConfiguration $configuration)
    {
        $this->io                   = $io;
        $this->projectConfiguration = $configuration;
    }

    /**
     * @param DockerCompose $compose
     *
     * @return array
     */
    public function __invoke(DockerCompose $compose)
    {
        $this->mode = $this->getInitializationMode();

        $configuration = [
            $this->getNetworkName(),
            $this->getNetworkTCPPort(),
            $this->getComposerHttpBasicCredentials(),
            $this->getSelectedServices(
                $compose->getServices(),
                ['varnish', 'solr', 'adminer', 'redisadmin']
            ),
            $this->getProvisioningFolderName(),
            $this->getComposeFileName(),
        ];

        return $configuration;
    }

    /**
     * @return string
     */
    public function getInitializationMode()
    {
        $standard     = self::INIT_STD;
        $withComposer = self::INIT_STD_COMPOSER_AUTH;
        $expert       = self::INIT_EXPERT;
        $question     = <<<END
eZ Launchpad will install a new architecture for you.
 Three modes are available:
  - <fg=cyan>{$standard}</>: All the services, no composer auth
  - <fg=cyan>{$withComposer}</>: Standard with ability to provide Composer Auth, useful for eZ Platform Enterprise
  - <fg=cyan>{$expert}</>: All the questions will be asked and you can select the services you want only
 Please select your <fg=yellow;options=bold>Init</>ialization mode
END;

        return $this->io->choice($question, self::$modes, self::INIT_STD);
    }

    /**
     * @return array
     */
    protected function getComposerHttpBasicCredentials()
    {
        if (!$this->requireComposerAuth()) {
            return [];
        }
        $credentials    = [];
        $endString      = '<fg=yellow;options=bold>Composer HTTP-BASIC</> for this project?';
        $questionString = 'Do you want to set '.$endString;
        while ($this->io->confirm($questionString, self::INIT_STD_COMPOSER_AUTH === $this->mode)) {
            list($host, $login, $password) = $this->getOneComposerHttpBasic();

            $credentials[]  = [$host, $login, $password];
            $questionString = 'Do you want to add another '.$endString;
        }

        return $credentials;
    }

    /**
     * @return array
     */
    protected function getOneComposerHttpBasic()
    {
        $pattern       = '^[a-zA-Z0-9\-\.]*$';
        $validatorHost = function ($value) use ($pattern) {
            return preg_match("/{$pattern}/", $value);
        };

        $message      = 'What is the <fg=yellow;options=bold>host</> on which you want to add credentials?';
        $errorMessage = "The host MUST respect {$pattern}.";
        $default      = 'updates.ez.no';
        $host         = $this->io->askQuestion($this->getQuestion($message, $default, $validatorHost, $errorMessage));
        $login        = $this->io->askQuestion($this->getQuestion('Login?'));
        $password     = $this->io->askQuestion($this->getQuestion('Password?'));

        return [$host, $login, $password];
    }

    /**
     * @return string
     */
    protected function getProvisioningFolderName()
    {
        $default = $this->projectConfiguration->get('provisioning.folder_name');
        if (empty($default)) {
            $default = 'provisioning';
        }

        if ($this->isStandardMode()) {
            return $default;
        }
        $pattern = '^[a-zA-Z0-9]*$';

        $validator = function ($value) use ($pattern) {
            return preg_match("/{$pattern}/", $value);
        };

        $message      = 'What is your preferred name for the <fg=yellow;options=bold>provisioning folder</>?';
        $errorMessage = "The name of the folder MUST respect {$pattern}.";

        return $this->io->askQuestion($this->getQuestion($message, $default, $validator, $errorMessage));
    }

    /**
     * @return string
     */
    public function getComposeFileName()
    {
        $default = $this->projectConfiguration->get('docker.compose_file');
        if (empty($default)) {
            $default = 'docker-compose.yml';
        }
        if ($this->isStandardMode()) {
            return $default;
        }

        $pattern = '^[a-zA-Z0-9\-]*\.yml$';

        $validator = function ($value) use ($pattern) {
            return preg_match("/{$pattern}/", $value);
        };

        $message      = 'What is your preferred filename for the <fg=yellow;options=bold>Docker Compose file</>?';
        $errorMessage = "The name of the filename MUST respect {$pattern}.";

        return $this->io->askQuestion($this->getQuestion($message, $default, $validator, $errorMessage));
    }

    /**
     * @param array $services
     * @param array $questionnable
     *
     * @return array
     */
    protected function getSelectedServices($services, $questionnable)
    {
        $selectedServices = [];
        foreach ($services as $name => $service) {
            if (in_array($name, $questionnable)) {
                if ($this->isStandardMode() ||
                    $this->io->confirm("Do you want the service <fg=yellow;options=bold>{$name}</>")) {
                    $selectedServices[] = $name;
                }
            } else {
                $selectedServices[] = $name;
            }
        }

        return $selectedServices;
    }

    /**
     * @return string
     */
    protected function getNetworkName()
    {
        $name      = getenv('USER').basename(getcwd());
        $default   = str_replace(['-', '_', '.'], '', strtolower($name));
        $pattern   = '^[a-zA-Z0-9]*$';
        $validator = function ($value) use ($pattern) {
            return preg_match("/{$pattern}/", $value);
        };

        if ($validator($default) && $this->isStandardMode()) {
            return $default;
        }

        $message      = 'Please select a name for the containers <fg=yellow;options=bold>Docker Network</>';
        $errorMessage = "The name of the network MUST respect {$pattern}.";

        return $this->io->askQuestion($this->getQuestion($message, $default, $validator, $errorMessage));
    }

    /**
     * @return int
     */
    protected function getNetworkTCPPort()
    {
        $default   = 42;
        $validator = function ($value) {
            if (($value > 0) && ($value <= 65)) {
                $socket = @fsockopen('127.0.0.1', intval("{$value}080"), $errno, $errstr, 5);
                if ($socket) {
                    fclose($socket);

                    return false;
                }

                return true;
            }

            return false;
        };

        if ($validator($default) && $this->isStandardMode()) {
            return $default;
        }

        $message      = 'What is the <fg=yellow;options=bold>TCP Port Prefix</> you want?';
        $errorMessage = 'The TCP Port Prefix is not correct (already used or not between 1 and 65.';

        return (int) $this->io->askQuestion($this->getQuestion($message, $default, $validator, $errorMessage));
    }

    /**
     * @param string        $message
     * @param null|string   $default
     * @param null|callable $validator
     * @param string        $exceptionMessage
     *
     * @return Question
     */
    protected function getQuestion($message, $default = null, $validator = null, $exceptionMessage = 'Entry not valid')
    {
        $question = new Question($message, $default);
        if (is_callable($validator)) {
            $question->setValidator(
                function ($value) use ($validator, $exceptionMessage) {
                    if (!$validator($value)) {
                        throw new \RuntimeException($exceptionMessage);
                    }

                    return $value;
                }
            );
        }

        return $question;
    }

    /**
     * @return bool
     */
    protected function isStandardMode()
    {
        return self::INIT_STD == $this->mode;
    }

    /**
     * @return bool
     */
    protected function requireComposerAuth()
    {
        return self::INIT_STD !== $this->mode;
    }
}
