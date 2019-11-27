<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

declare(strict_types=1);

/**
 * Get the TimeZone of the system if possible.
 */
function getDefaultTimeZone(): string
{
    $timezone = 'UTC';

    if (is_link('/etc/localtime')) {
        // Mac OS X (and older Linuxes)
        // /etc/localtime is a symlink to the timezone in /usr/share/zoneinfo.
        $filename = readlink('/etc/localtime');
        if (0 === strpos($filename, '/usr/share/zoneinfo/')) {
            $timezone = substr($filename, 20);
        }
    } elseif (file_exists('/etc/timezone')) {
        // Ubuntu / Debian.
        $data = file_get_contents('/etc/timezone');
        if ($data) {
            $timezone = trim($data);
        }
    } elseif (file_exists('/etc/sysconfig/clock')) {
        // RHEL/CentOS
        $data = parse_ini_file('/etc/sysconfig/clock');
        if (!empty($data['ZONE'])) {
            $timezone = trim($data['ZONE']);
        }
    }

    return $timezone;
}

function MacOSPatherize(string $path): string
{
    if ('Darwin' !== PHP_OS) {
        return $path;
    }

    static $isCatalina = null;

    if (null === $isCatalina) {
        if (exec('defaults read loginwindow SystemVersionStampAsString', $output, $returnCode)) {
            $parts = explode('.', $output[0]);
            $major = (int) $parts[0];
            $minor = (int) $parts[1];
            $isCatalina = ($major >= 10) && ($minor >= 15);
        } else {
            $isCatalina = false;
        }
    }

    if (!$isCatalina) {
        return $path;
    }

    return str_replace('/Users', '/System/Volumes/Data/Users', $path);
}

function githubFetch(string $url, bool $toJson = true)
{
    $context = stream_context_create(
        [
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: eZ Launchpad Installer',
                ],
            ],
        ]
    );

    $content = @file_get_contents($url, false, $context);

    if (false === $content) {
        return null;
    }

    if (false === $toJson) {
        return $content;
    }

    return json_decode($content, false);
}

function normalizeVersion(string $versionName): int
{
    if ('v' !== $versionName[0]) {
        return 0;
    }
    $version = substr($versionName, 1);
    $result = preg_replace('/([^ ]*) ([^ ]*) ([0-9.]*)-?([a-zA-z]*)/ui', '$3', $version);
    list($major, $minor, $patch) = explode('.', $result);

    return (int) $major * 10000 + (int) $minor * 100 + (int) $patch;
}
