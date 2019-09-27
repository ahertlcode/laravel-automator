#!/usr/bin/env php
<?php

//CREATE A TIMER CONSTANT
define('AUTOMATOR_START', microtime(true));

//import database configurations
$config = require __DIR__.'/config/config.php';

//set database settings from configurations
$servername = $config['host'];
$dbname = $config['database'];
$username = $config['user'];
$password = $config['password'];

//autoloading class
require 'autoload.php';

//create laravel application
$app_dir = '../../laravel/'.$dbname;
$app_dir_file = '../../laravel/'.$dbname.'/artisan';
if(!is_readable($app_dir_file)){
    $base_app_dir = '../../laravel';
    $laravel_command = "laravel new ".$dbname;
    exec("cd $base_app_dir && $laravel_command");
}else{
    echo "laravel application ".$dbname." exists, now attempting scafolding from database.\n";
}

if(!is_readable($app_dir_file)){
    echo "Laravel application not created, check terminal for error.";
    exit;
}

use Automator\Apimake;
use Automator\Appmake;
//$auto = new Automateapp();
//$auto->Automate();

//scafold laravel RESTful API from existing database
if($argv[1] == "--api")
{
    $auto = new Apimake();
    $auto->Automate();
}else if($argv[1] == "--app"){
    $auto = new Appmake();
    $auto->Automate();
}

$duration = microtime(true) - AUTOMATOR_START;
echo "\nTotal time spent by automator ".$duration."\n";
exit;