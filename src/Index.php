<?php

require_once('../vendor/autoload.php');

use \KDKeywords\Database;
use \Dotenv\Dotenv;
use \KDKeywords\AmazonAPI;

//load .env file
$dotenv = new Dotenv(dirname(dirname(__FILE__)));
$dotenv->load();
$pdo = Database::getInstance();


$execute = false;

if (in_array('-h', $argv) || in_array('--help', $argv)) {
    echoHelp();
    exit;
}

if (in_array('--search', $argv)) {
    $optionFrom  = getopt("f::");
    $from = !empty($optionFrom['f']) ? $optionFrom['f'] : null;
    $optionTo  = getopt("t::");
    $to = !empty($optionTo['t']) ? $optionTo['t'] : null;

    $amazonApi = new AmazonAPI($pdo);
    $amazonApi->search($from, $to);
    $execute = true;
    exit;
}

if (!$execute) {
    echoHelp();
    exit;
}


function echoHelp()
{
    echo "\n ****************************************************************************";
    echo "\n *    _      _      _             KDKeywords             _      _      _    *";
    echo "\n * __(.)< __(.)> __(.)=               #                >(.)__ <(.)__ =(.)__ *";
    echo "\n * \___)  \___)  \___)              V 1.0               (___/  (___/  (___/ *";
    echo "\n ****************************************************************************";
    echo "\n\n";
    echo PHP_EOL . ' Usage: ' . basename(__FILE__) . ' [-h] for help';
    echo PHP_EOL;
    echo PHP_EOL . '';
    echo PHP_EOL;
    echo PHP_EOL . ' Options:';
    echo PHP_EOL;
    echo PHP_EOL . '  --search  [-f=] [-t=]        Run Amazon Product API itemSearch';
    echo PHP_EOL;
    echo PHP_EOL;
    echo PHP_EOL;
}