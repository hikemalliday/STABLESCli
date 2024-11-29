<?php

// $this->db = new PDO("sqlite3:" . $this->dbPath);
namespace STABLESCli\Controllers;

use PDO;
use InvalidArgumentException;
use PDOException;

class EqDirController {
    private $db;
    private $parsed_campout_object;
    private $colors = [
        'red' => "\033[31m",
        'green' => "\033[32m",
        'yellow' => "\033[33m",
        'blue' => "\033[34m",
        'reset' => "\033[0m",
    ];

    public function __construct(array $kwargs) {
        $this->db = $kwargs['db'];
            if (!($this->db instanceof PDO)) {
                throw new InvalidArgumentException("The db parameter must be an instance of PDO.");
            }
    }

    public function getEqDir() {
        try {
            $eq_dir_query = "SELECT * FROM eqDir";      
            $stmt = $this->db->prepare($eq_dir_query);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $eq_dir = $results[0]["eqDir"];
            return $eq_dir;
        } catch (PDOException $e) {
            echo "getEqDir error: " . $e->getMessage() . "\n";
        }
    }
    public function setEqDir(string $eq_dir)
    {
        try {
            $eq_dir_query = "UPDATE eqDir SET eqDir = :eq_dir";
            $stmt = $this->db->prepare($eq_dir_query);
            $stmt->bindParam(':eq_dir', $eq_dir);
            $stmt->execute();
            return $this->colors['green'] . "Eq dir set:" . $eq_dir . $this->colors['reset'] . "\n";
        } catch (PDOException $e){
            return $this->colors['red'] . "setEqDir error: " . $e->getMessage() . "\n";
        }

    }
}