<?php
$relationships = getenv('PLATFORM_RELATIONSHIPS');
if ($relationships) {
    $relationships = json_decode(base64_decode($relationships), true);
    if (isset($relationships['database'][0])) {
        $database = $relationships['database'][0];
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
        if (!empty($database['port'])) {
            $string .= " -P {$database['port']}";
        }
        $string .= " {$database['path']}";
        echo $string;
    }
}
