<?php

namespace STABLESCli;
require_once __DIR__ . "/vendor/autoload.php";
use PDO;
use STABLESCli\Controllers\InterfaceController;


$db_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'STABLESCli' . DIRECTORY_SEPARATOR . 'master.db';
$db = new PDO("sqlite:$db_path");
$interface_controller = new InterfaceController(['db' => $db]);
$interface_controller->start();