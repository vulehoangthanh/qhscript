<?php
include "function-helper.php";
$rootPass = '';
$dir = '/mnt/volume-demo-bethemes/bethemes-demo/sources';
$domain = 'utech123.com';
$webRootDir = "/var/www";
$dirs = scandir($dir);
$dbDirs = "/mnt/volume-demo-bethemes/bethemes-demo/databases";

foreach($dirs as $d) {
    if($d == '.' || $d == '..')
        continue;
    if(preg_match('/\.zip/', $d)) continue;
    if(! preg_match('/^([\w\d_]+)\.wordpress\.local$/',$d, $matches)) continue;

    $site = "{$matches[1]}.{$domain}";

    if(file_exists("$webRootDir/{$site}/ee-config.php"))
        continue;

    $dbFile = "{$dbDirs}/{$d}.sql";
    $siteDBFile = "{$dbDirs}/{$site}.sql";

    if(!file_exists($dbFile)) {
        echo "{$dbFile} Not Exist Database File";
        continue;
    }

    $wpConfigFile = "{$dir}/{$d}/wp-config.php";
    if(!file_exists($wpConfigFile)) {
        echo "{$wpConfigFile} Not Exist";
        continue;
    }


    try {
        //create site
        $ee_site = shell_exec("ee site create {$site} --php --mysql");

        $nginxConfigFile = "/etc/nginx/sites-available/{$site}";
        if(! file_exists($nginxConfigFile))
        {
            echo "Not Exist {$nginxConfigFile}";
            continue;
        }


        //replace NGINX CONFIG FILE;
        $configNginxContent = file_get_contents($nginxConfigFile);
        $configNginxContent = str_replace("/var/www/{$site}/htdocs", "{$dir}/{$d}", $configNginxContent);
        $configNginxContent = str_replace("php.conf", "php7.conf", $configNginxContent);
        $configNginxContent = str_replace("locations.conf", "locations-php7.conf", $configNginxContent);
        file_put_contents($nginxConfigFile, $configNginxContent);

        //Read Database Info
        $ee_config = file_get_contents("{$webRootDir}/{$site}/ee-config.php");

        //Parse Account
        preg_match("/\'DB_NAME\'\, \'([\w\d_-]+)\'/", $ee_config, $matches);
        $DB_NAME = $matches[1];

        preg_match("/\'DB_USER\'\, \'([\w\d_-]+)\'/", $ee_config, $matches);
        $DB_USER = $matches[1];

        preg_match("/\'DB_PASSWORD\'\, \'([\w\d_-]+)\'/", $ee_config, $matches);
        $DB_PASS = $matches[1];

        //Wp_config file
        $wp_config = file_get_contents($wpConfigFile);
        $wp_config = preg_replace("/\'DB_NAME\'\,\s\'([\w\d\._-]+)\'/", "'DB_NAME', '{$DB_NAME}'", $wp_config);
        $wp_config = preg_replace("/\'DB_USER\'\,\s\'([\w\d\._-]+)\'/", "'DB_USER', '{$DB_USER}'", $wp_config);
        $wp_config = preg_replace("/\'DB_PASSWORD\'\,\s\'([\w\d\._-]+)?\'/", "'DB_PASSWORD', '{$DB_PASS}'", $wp_config);
        file_put_contents($wpConfigFile, $wp_config);

        //Import Database
        $dbContent = file_get_contents($dbFile);
        $dbContent = str_replace("{$d}", $site, $dbContent);
        $dbContent = str_replace("http://wordpress.local/", "http://{$site}/", $dbContent);
        file_put_contents($siteDBFile, $dbContent);

        shell_exec("mysql -u{$DB_USER} -p{$DB_PASS} {$DB_NAME} < {$siteDBFile}");

        $conn = mysqli_connect("127.0.0.1", $DB_USER, $DB_PASS, $DB_NAME);

        //replace in content of betheme
        betheme_content_replace($conn, $d, $site);
        removeWidgets($conn);
        replaceSlides($conn, $d, $site);

        mysqli_close($conn);
        //Config File
        echo "{$site} Successfull \n";
    } catch (Exception $e) {
        echo $e->getMessage() . "\n";
    }
    shell_exec("service nginx reload");
    shell_exec("chown -R www-data:www-data {$dir}");
}