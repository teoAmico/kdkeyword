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

    public function search($start = null, $end = null){
        echo 'TODO  search methods'.PHP_EOL;
        echo $start . PHP_EOL;
        echo $end . PHP_EOL;

    }


}