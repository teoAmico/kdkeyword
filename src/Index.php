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
echo PHP_EOL;
echo "  _  __   _ _  __                                _      ".PHP_EOL;
echo " | |/ /__| | |/ /___ _   ___      _____  _ __ __| |___  ".PHP_EOL;
echo " | ' // _` | ' // _ \ | | \ \ /\ / / _ \| '__/ _` / __| ".PHP_EOL;
echo " | . \ (_| | . \  __/ |_| |\ V  V / (_) | | | (_| \__ \ ".PHP_EOL;
echo " |_|\_\__,_|_|\_\___|\__, | \_/\_/ \___/|_|  \__,_|___/ ".PHP_EOL;
echo "                     |___/                              ".PHP_EOL;
echo PHP_EOL;

if (in_array('-h', $argv) || in_array('--help', $argv)) {
    echoHelp();
    exit;
}

if (in_array('--search', $argv)) {
    $optionFrom = getopt("f::");
    $from = !empty($optionFrom['f']) ? $optionFrom['f'] : null;
    $optionTo = getopt("t::");
    $to = !empty($optionTo['t']) ? $optionTo['t'] : null;

    $amazonApi = new AmazonAPI($pdo);

    $params = array(
        "Service" => "AWSECommerceService",
        "Operation" => "ItemSearch",
        "AWSAccessKeyId" => getenv('AWS_ACCESSKEY_ID'),
        "AssociateTag" => getenv('AWS_ASSOCIATE_TAG'),
        "SearchIndex" => "KindleStore",
        "ResponseGroup" => "BrowseNodes,EditorialReview,ItemAttributes,Similarities,SalesRank",
        "Sort" => "salesrank",
        "BrowseNode" => "157325011"
    );

    $amazonApi->search($params, $from, $to);
    $execute = true;
    exit;
}

if (!$execute) {
    echoHelp();
    exit;
}


function echoHelp()
{
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