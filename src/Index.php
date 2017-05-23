<?php

require_once('../vendor/autoload.php');

use \KDKeywords\Database;
use \Dotenv\Dotenv;
use \GuzzleHttp\Cleint;
use \KDKeywords\AmazonAPI;
use \League\CLImate\CLImate;

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
    'search' => [
        'prefix' => 's',
        'longPrefix' => 'search',
        'description' => 'Run Amazon Product API itemSearch',
        'noValue' => true,
        'required' => true,
    ],
    'from' => [
        'prefix' => 'f',
        'longPrefix' => 'from',
        'description' => 'Number of the page to start the itemSearch',
        'castTo' => 'int',
        'required' => false,
    ],
    'to' => [
        'prefix' => 't',
        'longPrefix' => 'to',
        'description' => 'Number of the page to end the itemSearch',
        'castTo' => 'int',
        'required' => false,
    ]
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

if ($terminal->arguments->defined('search')) {

    $from = $terminal->arguments->defined('from') ? $terminal->arguments->get('from') : null;
    $to =  $terminal->arguments->defined('to') ? $terminal->arguments->get('to') : null;
    $client = new GuzzleHttp\Client();
    $amazonApi = new AmazonAPI($terminal,$pdo,$client);

    $params = array(
        "BrowseNode" => "157325011",
        "SearchIndex" => "KindleStore",
        "ResponseGroup" => "BrowseNodes,EditorialReview,ItemAttributes,Similarities,SalesRank",
        "Sort" => "salesrank",
        'EndPoint'=>"webservices.amazon.com"
    );

    $amazonApi->search($params, $from, $to);
    exit;
}



