<?php namespace KDKeywords;

use \PDO;
use \League\CLImate\CLImate;

class Database
{
     /*
     * Define a static property that hold class instance
     */

    private static $pdo;

    /*
     * Create and return only one instance of this class
     */    
    public static function getInstance(CLImate $terminal)
    {
        if(empty(Database::$pdo)){
            $db = new Database();
            Database::$pdo = $db->connect($terminal);
        }
        return Database::$pdo;
    }

    //make a constructor private so this class cannot be instantiate
    private function __construct()
    {
        ;
    }
    
    private function connect($terminal){
        try{
            $host = getenv('DB_HOST');
            $db   = getenv('DB_NAME');
            $user = getenv('DB_USER');
            $pass = getenv('DB_PASSWORD');
            $charset = getenv('DB_CHARSET');

            $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
            $opt = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            return new \PDO($dsn, $user, $pass, $opt);
        }catch(\Exception $e){
            $terminal->White()->backgroundRed($e->getMessage());
            exit();
        }
        return null;
    }
}
