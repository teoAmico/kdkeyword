<?php

require_once('../vendor/autoload.php');

use \KDKeywords\Database;
use \Dotenv\Dotenv;
use \League\CLImate\CLImate;
use \KDKeywords\ImportBooks;


//load global variable
$dotenv = new Dotenv(dirname(dirname(__FILE__)));
$dotenv->load();
//create terminal instance
$terminal = new CLImate();
$terminal->addArt(__DIR__);
// draw nice logo in terminal
$terminal->draw('logo');
// create pdo instance
$pdo = Database::getInstance($terminal);

//create terminal option configuration
$terminal->arguments->add([

    'help' => [
        'prefix' => 'h',
        'longPrefix' => 'help',
        'description' => 'Show usage menu',
        'required' => false,
        'noValue' => true,

    ],
    'import' => [
        'prefix' => 'i',
        'longPrefix' => 'import',
        'description' => 'Parse Amazon data feeds and import data into books table',
        'noValue' => true,
        'required' => true,
    ],
]);

try {
    $terminal->arguments->parse();
} catch (\Exception $e) {
    $terminal->usage();
    exit;
}

if ($terminal->arguments->defined('help')) {
    $terminal->usage();
    exit;
}

if ($terminal->arguments->defined('import')) {
    $importer = new ImportBooks($terminal, $pdo);
    $importer->run();
    exit();
}

