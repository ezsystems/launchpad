<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Launchpad\Console;

use eZ\Launchpad\Console;
use eZ\Launchpad\DependencyInjection\CommandPass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;

/**
 * Class ApplicationFactory.
 */
class ApplicationFactory
{
    /**
     * Create the Application.
     *
     * @param bool   $autoExit Default: true
     * @param string $env      Default: prod
     *
     * @return Application
     */
    public static function create($autoExit = true, $env = 'prod')
    {
        define('EZ_HOME', getenv('HOME').'/.ezlaunchpad');
        $container = new ContainerBuilder();
        $container->addCompilerPass(new CommandPass($env));
        $container->addCompilerPass(new RegisterListenersPass());
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__));
        $loader->load(__DIR__.'/../../config/services.yml');
        $loader->load(__DIR__.'/../../config/commands.yml');
        $application = new Console\Application();
        $application->setContainer($container);
        $application->setEnv($env);
        $application->setName('eZ Launchpad');
        $application->setVersion('@package_version@'.(($env != 'prod') ? '-dev' : ''));
        $application->setAutoExit($autoExit);

        return $application;
    }
}
