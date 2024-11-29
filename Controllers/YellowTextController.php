<?php

// $this->db = new PDO("sqlite3:" . $this->dbPath);
namespace STABLESCli\Controllers;

use PDO;
use InvalidArgumentException;
use PDOException;

class YellowTextController {
    private $eq_dir;
    private $current_page;
    private $dbPath;
    private $db;
    private$parsed_yellowtext_object = [];
    private $colors = [
        'red' => "\033[31m",
        'green' => "\033[32m",
        'yellow' => "\033[33m",
        'blue' => "\033[34m",
        'reset' => "\033[0m",
    ];

    public function __construct(array $kwargs) {
        $this->eq_dir = $kwargs['eq_dir'] ?? '';
        $this->db = $kwargs['db'];
            if (!($this->db instanceof PDO)) {
                throw new InvalidArgumentException("The db parameter must be an instance of PDO.");
            }
        $this->current_page = 0;
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function getYellowTextQuery(array $kwargs) {
        try {
            $zone_name = '%' . strtolower($kwargs['zone_name'] ?? '') . '%';
            $char_name = '%' . strtolower($kwargs['char_name'] ?? '') . '%';
            $page_size = $kwargs['page_size'] ?? 75;
            $offset = $this->current_page * $page_size; 
            $yellow_text_query = "SELECT * 
                            FROM yellowText 
                            WHERE killer LIKE :char_name
                            AND zone LIKE :zone_name
                            LIMIT $page_size OFFSET $offset";

            $stmt = $this->db->prepare($yellow_text_query);
            $stmt->bindParam(':char_name', $char_name);
            $stmt->bindParam(':zone_name', $zone_name);

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "getYellowText error: " . $e->getMessage() . "\n";
        }
    }

    public function parseYellowtext(){
        echo "Yellow text parse started, parse time varies based on log file sizes...". "\n";
        $logs_dir = $this->eq_dir . "/" . "Logs/";
        if (is_dir($logs_dir) && ($handle = opendir($logs_dir))) {
            while (false !== ($file_name = readdir($handle))) {
                if ($file_name != "."&& $file_name != "..") $this->_readYellowTextObject($file_name);
            }
            closedir($handle);
            $this->_insertYellowTextObject($this->parsed_yellowtext_object);
        } else {
            echo $this->colors['red'] . "$this->eq_dir is not a valid directory or could not be opened.". $this->colors['reset'] . "\n";
            return;
        }
        echo $this->colors['green'] . "Yellow text parse complete." . $this->colors['reset'] . "\n";
    }

    public function _readYellowTextObject($file_name) {
        $pattern = '/^eqlog_([A-Za-z]+)_P1999PVP\.txt$/';
        $file_path = $this->eq_dir . '/' . 'Logs/' . $file_name;
        if (!(preg_match($pattern, $file_name, $matches))) return;
        if (!is_file($file_path) && !is_readable($file_path)) return;
        $handle = fopen($file_path, 'r');

        $pattern = '/\[(.+?)\] \[PvP\] (.+?) <.+?> has been defeated by (.+?) <.+?> in (.+?)!/';
        while (($line = fgets($handle)) !== false) {
            if (preg_match($pattern, $line, $matches)) {
                $time_stamp = $matches[1];
                $victim = $matches[2];
                $killer = $matches[3];
                $zone = $matches[4];
                $this->parsed_yellowtext_object[] = [
                    "timeStamp" => $time_stamp,
                    "victim" => $victim,
                    "killer" => $killer,
                    "zone" => $zone,
                ];
            } 
        }
    }
    private function _insertYellowTextObject($parsed_yellowtext_object)
{
   
    $this->db->beginTransaction();
    $insert_query = "INSERT INTO yellowText (killer, victim, zone, timeStamp) 
                 VALUES (:killer, :victim, :zone, :timeStamp)";
    
    $stmt = $this->db->prepare($insert_query);
    
    foreach ($parsed_yellowtext_object as $row) {
        $stmt->bindValue(':killer', $row['killer'], PDO::PARAM_STR);
        $stmt->bindValue(':victim', $row['victim'], PDO::PARAM_STR);
        $stmt->bindValue(':zone', $row["zone"], PDO::PARAM_STR);
        $stmt->bindValue(':timeStamp', $row["timeStamp"], PDO::PARAM_STR);
        $stmt->execute(); 
    }
    $this->db->commit();
}
}