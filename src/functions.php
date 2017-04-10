<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license   For full copyright and license information view LICENSE file distributed with this source code.
 */

/**
 * Get the TimeZone of the system if possible.
 *
 * @return string
 */
function getDefaultTimeZone()
{
    $timezone = 'UTC';

    if (is_link('/etc/localtime')) {
        // Mac OS X (and older Linuxes)
        // /etc/localtime is a symlink to the timezone in /usr/share/zoneinfo.
        $filename = readlink('/etc/localtime');
        if (strpos($filename, '/usr/share/zoneinfo/') === 0) {
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
