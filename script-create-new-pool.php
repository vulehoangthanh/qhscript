<?php
include "function-helper.php";

echo "Input Site Name: ";
$handle = fopen ("php://stdin","r");
$site = fgets($handle);
echo "Input User Name: ";
$handle = fopen ("php://stdin","r");
$user = fgets($handle);
$site = trim($site);
$user = trim($user);


$s = @shell_exec("cat /etc/passwd");
if(! preg_match("/{$user}/", $s)) {
	echo "user {$user} not exists";
	//generate Password For User
	$pass = uniqid();
	@shell_exec("echo -e \"{$pass}\n{$pass}\" | passwd {$user}\"");
	echo "Host: {$site}\nPort: 22\nUser: {$user}\n Password: {$pass}";
}
unset($s);

@shell_exec("mkdir /home/{$user}");
@shell_exec("mkdir /home/{$user}/{$site}");
@shell_exec("mkdir /home/{$user}/{$site}/htdocs");
@shell_exec("groupadd {$user}");
@shell_exec("useradd -g {$user} {$user}");
@shell_exec("chown -R {$user}:{$user} /home/{$user}");
$pool_file = "/etc/php/7.0/fpm/pool.d/{$user}.conf";

if(! file_exists($pool_file)) {
	$pool_content = file_get_contents('config/pool.txt');
	$pool_content = str_replace("##site##", $user, $pool_content);	
	file_put_contents($pool_file, $pool_content);
}

$nginx_config = "/etc/nginx/sites-available/{$site}";
$dir_bk_nginx = "/etc/nginx/sites-available-bk";
$nginx_config_bk = "{$dir_bk_nginx}/{$site}";
if(!is_dir($dir_bk_nginx)) {
	mkdir($dir_bk_nginx);	
}

if(! file_exists($nginx_config_bk)) {
	file_put_contents($nginx_config_bk, file_get_contents($nginx_config));

	echo "{$site} exists config";
}

$sock = "/var/run/php7-fpm-{$user}.sock";
$root = "root /var/www/{$site}/htdocs";

$nginx_config_content = file_get_contents($nginx_config);
$nginx_config_content = str_replace("include common/php7.conf;", file_get_contents("config/common-php7.conf"), $nginx_config_content);
$nginx_config_content = str_replace("##sock##", $sock, $nginx_config_content);
$nginx_config_content = str_replace($root, "root /home/{$user}/{$site}/htdocs", $nginx_config_content);

file_put_contents($nginx_config, $nginx_config_content);
//restart 
// shell_exec("service php7.0-fpm restart");
