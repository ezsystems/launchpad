<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

namespace eZ\Launchpad\Command\Docker;

use eZ\Launchpad\Core\Client\Docker;
use eZ\Launchpad\Core\DockerCompose;
use eZ\Launchpad\Core\TaskExecutor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Filesystem\Filesystem;

final class InitializeSkeleton extends Initialize
{
    protected function configure(): void
    {
        parent::configure();
        $this->setName('docker:initialize:skeleton')->setDescription(
            'Initialize without all of eZ. <fg=red;options=bold>(REALLY experimental)</>'
        );
        $this->setAliases(['docker:init:skeleton', 'init-skeleton']);
        $this->setHidden(true);
    }

    protected function innerInitialize(
        Docker $dockerClient,
        DockerCompose $compose,
        $composeFilePath,
        InputInterface $input
    ): void {
        $compose->cleanForInitializeSkeleton();
        $compose->dump($composeFilePath);

        $fs = new Filesystem();
        // Empty Nginx
        $nginxEmptyConf = <<<END
# GZIP
gzip on;
gzip_disable "msie6";
gzip_proxied any;
gzip_comp_level 6;
gzip_buffers 16 8k;
gzip_http_version 1.1;
gzip_types text/plain text/css application/json application/x-javascript text/xml application/xml 
           application/xml+rss text/javascript;

# DEV MODE
server {
    listen 80;
    server_name _;
    # Project Root = project-path-container = /var/www/html/project
    # Would be great to get that from ENV var PROJECTMAPPINGFOLDER
    root "/var/www/html/project";

    # upload max size
    client_max_body_size 40M;

    # FPM fastcgi_read_timeout
    fastcgi_read_timeout 30;

    location / {
        // Your configuration comes here.
        // For instance:
        // fastcgi_pass engine:9000;
        // fastcgi_param SYMFONY_ENV dev;
    }
}

# PROD MODE - Symfony Reverse Proxy
server {
    listen 81;
    server_name _;
    # Project Root = project-path-container = /var/www/html/project
    # Would be great to get that from ENV var PROJECTMAPPINGFOLDER
    root "/var/www/html/project";

    # upload max size
    client_max_body_size 40M;

    # FPM fastcgi_read_timeout
    fastcgi_read_timeout 30;

    location / {
        // Your configuration comes here.
        // For instance:
        // fastcgi_pass engine:9000;
        // fastcgi_param SYMFONY_ENV prod;
    }
}

# PROD MODE - Varnish
server {
    listen 82;
    server_name _;
    # Project Root = project-path-container = /var/www/html/project
    # Would be great to get that from ENV var PROJECTMAPPINGFOLDER
    root "/var/www/html/project";

    # upload max size
    client_max_body_size 40M;

    # FPM fastcgi_read_timeout
    fastcgi_read_timeout 30;

    location / {
        // Your configuration comes here.
        // For instance:
        // fastcgi_pass engine:9000;
        // fastcgi_param SYMFONY_ENV prod;
        // fastcgi_param SYMFONY_HTTP_CACHE 0;
        // fastcgi_param SYMFONY_TRUSTED_PROXIES "127.0.0.1,localhost,172.0.0.0/8";
    }
}

END;
        // Empty Varnish
        $varnishEmptyConf = <<<END
vcl 4.0;
import std;
backend ezplatform {
    .host = "nginx";
    .port = "82";
}
sub vcl_recv {
    // Set the backend
    set req.backend_hint = ezplatform;
}}
END;

        $fs->dumpFile(
            "{$this->projectConfiguration->get('provisioning.folder_name')}/dev/nginx/nginx.conf",
            $nginxEmptyConf
        );
        $fs->dumpFile(
            "{$this->projectConfiguration->get('provisioning.folder_name')}/dev/varnish/varnish.conf",
            $varnishEmptyConf
        );

        $dockerClient->build(['--no-cache']);
        $dockerClient->up(['-d']);

        $executor = new TaskExecutor($dockerClient, $this->projectConfiguration, $this->requiredRecipes);
        $executor->composerInstall();
    }
}
