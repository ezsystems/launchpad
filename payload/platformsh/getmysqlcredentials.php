<?php
include(__DIR__."/../ezplatform/vendor/autoload.php");

use Platformsh\ConfigReader\Config as PlatformshConfig;

$config = new PlatformshConfig();
if (isset($config->relationships['database'][0])) {
    $database = $config->relationships['database'][0];
    $string   = '';
    if (!empty($database['username'])) {
        $string .= " -u {$database['username']}";
    }
    if (!empty($database['password'])) {
        $string .= " -p{$database['password']}";
    }
    if (!empty($database['host'])) {
        $string .= " -h {$database['host']}";
    }
    $string .= " {$database['path']}";
    echo $string;
}
