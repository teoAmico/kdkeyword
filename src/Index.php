<?php

require_once('../vendor/autoload.php');

use \KDKeywords\Database;
use \Dotenv\Dotenv;
use \GuzzleHttp\Cleint;
use \KDKeywords\AmazonAPI;

$dotenv = new Dotenv(dirname(dirname(__FILE__)));
$dotenv->load();
$pdo = Database::getInstance();
$climate = new League\CLImate\CLImate;
$climate->addArt(__DIR__);
$climate->draw('logo');
$climate->arguments->add([
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
    $climate->arguments->parse();
} catch (\Exception $e) {
    $climate->usage();
    exit;
}

if ($climate->arguments->defined('help')) {
    $climate->usage();
    exit;
}

if ($climate->arguments->defined('search')) {

    $from = $climate->arguments->defined('from') ? $climate->arguments->get('from') : null;
    $to =  $climate->arguments->defined('to') ? $climate->arguments->get('to') : null;
    $client = new GuzzleHttp\Client();
    $amazonApi = new AmazonAPI($pdo,$client);

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



