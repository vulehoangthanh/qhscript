<?php
include "function-helper.php";
$dir = '/mnt/volume-demo-bethemes/bethemes-demo/sources';
$domain = 'utech123.com';
$webRootDir = "/var/www";
$dirs = scandir($dir);

foreach($dirs as $d) {
    if ($d == '.' || $d == '..')
        continue;
    if (preg_match('/\.zip/', $d)) continue;
    if (!preg_match('/^([\w\d_]+)\.wordpress\.local$/', $d, $matches)) continue;

    $site = "{$matches[1]}.{$domain}";

    if (! file_exists("{$webRootDir}/{$site}/ee-config.php"))
    {
        echo "{$site} ee config not exists";
        continue;
    }
    //Read Database Info
    $ee_config = file_get_contents("{$webRootDir}/{$site}/ee-config.php");

    //Parse Account
    preg_match("/\'DB_NAME\'\, \'([\w\d_-]+)\'/", $ee_config, $matches);
    $DB_NAME = $matches[1];

    preg_match("/\'DB_USER\'\, \'([\w\d_-]+)\'/", $ee_config, $matches);
    $DB_USER = $matches[1];

    preg_match("/\'DB_PASSWORD\'\, \'([\w\d_-]+)\'/", $ee_config, $matches);
    $DB_PASS = $matches[1];

    $conn = mysqli_connect("127.0.0.1", $DB_USER, $DB_PASS, $DB_NAME);
    betheme_content_replace($conn, $d, $site);
    mysqli_close($conn);

    echo "{$site} Successfull\n";
}