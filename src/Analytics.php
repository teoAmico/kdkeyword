<?php
date_default_timezone_set('Europe/London');
require_once('../vendor/autoload.php');

use \KDKeywords\Database;
use \Dotenv\Dotenv;
use \League\CLImate\CLImate;
use \KDKeywords\KeywordsExtractor;


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
    'extractor' => [
        'prefix' => 'e',
        'longPrefix' => 'extractor',
        'description' => 'Extract keywords from books titles',
        'noValue' => true,
        'required' => false,
    ],
    'analysis' => [
        'prefix' => 'a',
        'longPrefix' => 'analysis',
        'description' => 'Analysis keywords from books titles',
        'noValue' => true,
        'required' => false,
    ],
    'salesrank'=> [
        'prefix' => 's',
        'longPrefix' => 'salesrank',
        'description' => 'Fix Salesrank',
        'noValue' => true,
        'required' => false,
    ],
]);

try {
    $terminal->arguments->parse();
} catch (\Exception $e) {
    var_dump($e->getMessage());
    $terminal->usage();
    exit;
}

if ($terminal->arguments->defined('help')) {
    $terminal->usage();
    exit;
}

if ($terminal->arguments->defined('extractor')) {
    $extractor = new KeywordsExtractor($terminal, $pdo);
    $extractor->extract();
    exit();
}

if ($terminal->arguments->defined('analysis')) {
    $extractor = new KeywordsExtractor($terminal, $pdo);
    $extractor->analysis();
    exit();
}

if ($terminal->arguments->defined('salesrank')) {
    $extractor = new KeywordsExtractor($terminal, $pdo);
    $extractor->fixSalesRank();
    exit();
}