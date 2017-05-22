<?php

require_once('../vendor/autoload.php');

use \KDKeywords\Database;
use \Dotenv\Dotenv;

//load .env file
$dotenv = new Dotenv(dirname(dirname(__FILE__)));
$dotenv->load();
$pdo = Database::getInstance();


