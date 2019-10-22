<?php

/**
 * If you are using a single database, you don't need to specify any argument.
 * If you are setting multiple databases you could find the names in .platform/services.yml file as follow :
 *
 * mysqldb:
 *    configuration:
 *       schemas:
 *         - mydbname1
 *         - mydbname1

 * if you need to get credantials, you could run the command with database name prefixed by "database_"
 * e.g: php provisioning/platformsh/getmysqlcredentials.php database_<dbName>
 *
 * if you are not sure, you could also list the database names as follow :
 * php provisioning/platformsh/getmysqlcredentials.php --list
 */

$relationships = getenv( 'PLATFORM_RELATIONSHIPS' );
if ( !$relationships ) exit;

$relationships = json_decode( base64_decode( $relationships ), true );
$relationship_name = "database";

if ( count( $argv ) >= 2)
{
    if ( $argv[1] === '--list' )
    {
        foreach ($relationships as $key => $value) {
            $pos = strpos($key, "database_");
            if ($pos !== false) {
                echo $key."\n";
            }
        }
        exit;
    }
    $relationship_name = $argv[1];
}

if ( isset( $relationships[$relationship_name][0] ) )
{
    $database = $relationships[$relationship_name][0];
    $string   = '';
    if ( !empty( $database['username'] ) )
    {
        $string .= " -u {$database['username']}";
    }
    if ( !empty( $database['password'] ) )
    {
        $string .= " -p{$database['password']}";
    }
    if ( !empty( $database['host'] ) )
    {
        $string .= " -h {$database['host']}";
    }
    if ( !empty( $database['port'] ) )
    {
        $string .= " -P {$database['port']}";
    }
    $string .= " {$database['path']}";
    echo $string;
}