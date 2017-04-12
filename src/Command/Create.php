<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Command;

use eZ\Launchpad\Core\DockerCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Create.
 */
class Create extends DockerCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('docker:create')->setDescription('Create all the services.');
        $this->setAliases(['create']);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->dockerClient->build(['--no-cache']);
        $this->dockerClient->up(['-d']);

        //@todo refacto that with initialize to reuse
        $this->dockerClient->exec(
            '/var/www/html/project/composer_install.bash',
            [
                '--user', 'www-data',
            ],
            'engine'
        );

        // Composer Configuration
        foreach ($this->projectConfiguration->get('composer.http_basic') as $auth) {
            if (!isset($auth['host']) || !isset($auth['login']) || !isset($auth['password'])) {
                continue;
            }
            $this->dockerClient->exec(
                '/var/www/html/project/composer.phar config --global'.
                " http-basic.{$auth['host']} {$auth['login']} {$auth['password']}",
                ['--user', 'www-data'],
                'engine'
            );
        }

        $this->dockerClient->exec(
            '/var/www/html/project/ez_create.bash',
            [
                '--user', 'www-data',
            ],
            'engine'
        );

        $this->dockerClient->exec(
            '/var/www/html/project/import_dump.bash',
            [
                '--user', 'www-data',
            ],
            'engine'
        );
    }
}
