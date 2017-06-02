<?php

namespace KDKeywords;

use \PDO;
use \League\CLImate\CLImate;
use DonatelloZa\RakePlus\RakePlus;


class KeywordsExtractor
{
    protected $terminal;
    protected $pdo;

    public function __construct(CLImate $terminal, PDO $pdo)
    {
        $this->terminal = $terminal;
        $this->pdo = $pdo;
    }

    public function run()
    {
        $text = "Criteria of compatibility of a system of linear Diophantine equations, " .
            "strict inequations, and nonstrict inequations are considered. Upper bounds " .
            "for components of a minimal set of solutions and algorithms of construction " .
            "of minimal generating sets of solutions for all types of systems are given.";

        $phrases = RakePlus::create($text)->scores();

        print_r($phrases);
    }
}