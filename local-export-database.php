<?php
//export DB
$user = 'root';
$pass = 'root';
$dir = 'C:\xampp\htdocs\themes';

$dirs = scandir($dir);

foreach($dirs as $d) {
    if($d == '.' || $d == '..')
        continue;

    try {
        $sql = "database/{$d}.sql";
        $shell = "mysqldump -u{$user} {$d} > $sql";
        echo $shell."\n";
        shell_exec($shell);
    } catch (Exception $e) {
        echo $e->getMessage() . "\n";
    }
}