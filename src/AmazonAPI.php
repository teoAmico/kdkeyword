<?php

namespace KDKeywords;

use \KDKeywords\Database;

class AmazonAPI
{

    protected $pdo;

    public function __constructor(Database $pdo)
    {
        $this->pdo = $pdo;
    }

    public function search($params = [], $from = null, $to = null)
    {
        echo 'TODO  search methods' . PHP_EOL;
        echo $from . PHP_EOL;
        echo $to . PHP_EOL;


    }


}