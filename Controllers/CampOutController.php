<?php

// $this->db = new PDO("sqlite3:" . $this->dbPath);
namespace STABLESCli\Controllers;

use PDO;
use InvalidArgumentException;
use PDOException;

class CampOutController {
    private $eq_dir;
    private $current_page;
    private $db_path;
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
       
        $this->eq_dir = $kwargs['eq_dir'] ?? '';
        $this->db = $kwargs['db'];
            if (!($this->db instanceof PDO)) {
                throw new InvalidArgumentException("The db parameter must be an instance of PDO.");
            }
        $this->current_page = 0;
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function getCampOutQuery(array $kwargs) {
        try {
            $zone_name = '%' . strtolower($kwargs['zone_name'] ?? '') . '%';
            $char_name = '%' . strtolower($kwargs['char_name'] ?? '') . '%';
            $page_size = $kwargs['page_size'] ?? 75;
            $offset = $this->current_page * $page_size; 
            $camp_out_query = "SELECT * 
                            FROM campOut 
                            WHERE charName LIKE :char_name
                            AND zone LIKE :zone_name
                            LIMIT $page_size OFFSET $offset";
            $stmt = $this->db->prepare($camp_out_query);
            $stmt->bindParam(':char_name', $char_name);
            $stmt->bindParam(':zone_name', $zone_name);

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "getCampOut error: " . $e->getMessage() . "\n";
        }
    }
    public function parseCampOut()
    {
        echo "Campout parse started, parse time varies based on log file sizes..." . "\n";
        $logs_dir = $this->eq_dir . '/' . 'Logs/';
        if (is_dir($logs_dir) && ($handle = opendir($logs_dir))) {
            while (false !== ($file_name = readdir($handle))) {
                if ($file_name != "."&& $file_name != "..") $this->_readCampOutFile($file_name);
            }
            closedir($handle);
            $this->_insertCampOutObject($this->parsed_campout_object);
        } else {
            echo $this->colors['red'] . "$this->eq_dir is not a valid directory or could not be opened." . $this->colors['reset'] . "\n";
            return;
        }
        echo $this->colors['green'] . "Campout parse complete." . $this->colors['reset'] . "\n";
    }
    public function _readCampOutFile($file_name)
    {
        
        $pattern = '/^eqlog_([A-Za-z]+)_P1999PVP\.txt$/';
        $file_path = $this->eq_dir . '/' . 'Logs/' . $file_name;
        if (!(preg_match($pattern, $file_name, $matches))) return;
        if (!is_file($file_path) && !is_readable($file_path)) return;
        $char_name = $matches[1];
        $handle = fopen($file_path, 'r');
        $this->parsed_campout_object[$char_name] = [];

        $campout_location = "";
        $campout_time = "";
        $pattern = '/\[(.+?)\] You have entered (.+?)\./';
        
        while (($line = fgets($handle)) !== false) {
            if (preg_match($pattern, $line, $matches)) {
                $campout_time = $matches[1];
                $campout_location = $matches[2];
            } 
        }
        $this->parsed_campout_object[$char_name] = [
            "zone" => $campout_location,
            "timeStamp" => $campout_time
        ];
}
private function _insertCampOutObject($parsed_items_object)
{

    $this->db->beginTransaction();
    $insert_query = "INSERT INTO campOut (charName, zone, timeStamp) 
                 VALUES (:charName, :zone, :timeStamp)";
    
    $stmt = $this->db->prepare($insert_query);
    
    foreach ($parsed_items_object as $char_name => $data) {
        $stmt->bindValue(':charName', $char_name, PDO::PARAM_STR);
        $stmt->bindValue(':zone', $data["zone"], PDO::PARAM_STR);
        $stmt->bindValue(':timeStamp', $data["timeStamp"], PDO::PARAM_STR);
        $stmt->execute(); 
    }
    $this->db->commit();
}
}