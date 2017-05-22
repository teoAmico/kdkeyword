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


    }


}