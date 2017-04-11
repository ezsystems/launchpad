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
     * @param array $compose
     *
     * @return array
     */
    public function __invoke($compose)
    {
        return [
            $this->getNetworkName(),
            $this->getNetworkTCPPort(),
            $this->getSelectedServices($compose['services']),
            $this->getProvisioningFolderName(),
            $this->getComposeFileName(),
        ];
    }

    /**
     * @return string
     */
    protected function getProvisioningFolderName()
    {
        $pattern = '^[a-zA-Z0-9]*$';

        $validator = function ($value) use ($pattern) {
            return preg_match("/{$pattern}/", $value);
        };

        $message      = 'What is your preferred name for the <fg=yellow;options=bold>provisioning folder</>?';
        $errorMessage = "The name of the folder MUST respect {$pattern}.";
        $default      = $this->projectConfiguration->get('provisioning.folder_name');
        if (empty($default)) {
            $default = 'provisioning';
        }

        return $this->io->askQuestion($this->getQuestion($message, $default, $validator, $errorMessage));
    }

    /**
     * @return string
     */
    public function getComposeFileName()
    {
        $pattern = '^[a-zA-Z0-9\-]*\.yml$';

        $validator = function ($value) use ($pattern) {
            return preg_match("/{$pattern}/", $value);
        };

        $message      = 'What is your preferred filename for the <fg=yellow;options=bold>Docker Compose file</>?';
        $errorMessage = "The name of the filename MUST respect {$pattern}.";
        $default      = $this->projectConfiguration->get('docker.compose_file');
        if (empty($default)) {
            $default = 'docker-compose.yml';
        }

        return $this->io->askQuestion($this->getQuestion($message, $default, $validator, $errorMessage));
    }

    /**
     * @param array $services
     *
     * @return array
     */
    protected function getSelectedServices($services)
    {
        $selectedServices = [];
        foreach ($services as $name => $service) {
            $message            = "Do you want the service <fg=yellow;options=bold>{$name}</>";
            $default            = 'yes';
            $autocompleteValues = ['yes', 'no'];
            $errorMessage       = 'You MUST answer '.implode(' or ', $autocompleteValues);
            $validator          = function ($value) use ($autocompleteValues) {
                return in_array($value, $autocompleteValues);
            };

            $question = $this->getQuestion(
                $message,
                $default,
                $validator,
                $errorMessage
            )->setAutocompleterValues($autocompleteValues);
            if ($this->io->askQuestion($question) == 'yes') {
                $selectedServices[$name] = $service;
            }
        }

        return $selectedServices;
    }

    /**
     * @return string
     */
    protected function getNetworkName()
    {
        $pattern = '^[a-zA-Z0-9]*$';

        $validator = function ($value) use ($pattern) {
            return preg_match("/{$pattern}/", $value);
        };

        $message      = 'What is the name of the <fg=yellow;options=bold>network</>?';
        $errorMessage = "The name of the network MUST respect {$pattern}.";
        $default      = null;

        return $this->io->askQuestion($this->getQuestion($message, $default, $validator, $errorMessage));
    }

    /**
     * @return int
     */
    protected function getNetworkTCPPort()
    {
        $validator = function ($value) {
            return ($value > 0) && ($value <= 65);
        };

        $message      = 'What is the <fg=yellow;options=bold>TCP Port Prefix</> you want?';
        $errorMessage = 'The TCP Port Prefix MUST be between 1 and 65.';
        $default      = 42;

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
}
