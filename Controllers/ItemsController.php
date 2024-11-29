<?php

// $this->db = new PDO("sqlite3:" . $this->dbPath);
namespace STABLESCli\Controllers;

use PDO;
use InvalidArgumentException;
use PDOException;

class ItemsController {
    private $eq_dir;
    private $current_page;
    private $db;
    // After parse items is ran, this will be an object with key=char_name and val=2d array of rows
    private $parsed_items_object;
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

    public function getItemsQuery(array $kwargs) {
        try {
            $item_name = '%' . strtolower($kwargs['item_name'] ?? '') . '%';
            $char_name = '%' . strtolower($kwargs['char_name'] ?? '') . '%';
            #$page_size = $kwargs['page_size'] ?? 75;
            #$offset = $this->current_page * $page_size; 
            $items_query = "SELECT * 
                            FROM items 
                            WHERE itemName LIKE :item_name
                            AND charName LIKE :char_name";
                            #LIMIT $page_size OFFSET $offset"

            $stmt = $this->db->prepare($items_query);
            $stmt->bindParam(':item_name', $item_name);
            $stmt->bindParam(':char_name', $char_name);

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "getItems error: " . $e->getMessage();
            echo "\n";
        }
    }

    public function parseItems()
    {   
        echo "Items parse started..." . "\n";
        if (is_dir($this->eq_dir) && ($handle = opendir($this->eq_dir))) {
            // Loop through the directory entries
            while (false !== ($file_name = readdir($handle))) {
                if ($file_name !== '.' && $file_name !== '..') $this->_readItemsFile($file_name);
            }
            closedir($handle);
            $this->_insertItemsObject($this->parsed_items_object);
        } else {
            echo $this->colors['red'] . "$this->eq_dir is not a valid directory or could not be opened." . $this->colors['reset'] . "\n";
            return;
        }
        echo $this->colors['green'] . "Items parse complete." . $this->colors['reset'] . "\n";
    }

    private function _readItemsFile($file_name) 
    {
        $pattern = '/^([A-Za-z]+)-Inventory\.txt$/';
        $file_path = $this->eq_dir . '/' . $file_name;

        if (!(preg_match($pattern, $file_name,$matches))) return;
        if (!is_file($file_path) && !is_readable($file_path)) return;
        $char_name = $matches[1];
        $handle = fopen($file_path, 'r');
        $first_line = fgets($handle);
        $modified_date = date("m-d-Y", filemtime($file_path));
  
        if (strpos($first_line, "Location") === false) return;
        
        $this->parsed_items_object[$char_name] = [];
        
        while (($line = fgets($handle)) !== false) {
            if (strpos($first_line, "Location")) continue;
            
            $line_as_array = explode("\t", $line);
            $line_as_array[] = $modified_date;
            $this->parsed_items_object[$char_name][] = $line_as_array;
        }
    }

    private function _insertItemsObject($parsed_items_object)
    {
        
        $this->db->beginTransaction();
        $insert_query = "INSERT INTO items (charName, itemLocation, itemName, itemId, itemCount, fileDate) 
                     VALUES (:charName, :itemLocation, :itemName, :itemId, :itemCount, :fileDate)";
        
        $stmt = $this->db->prepare($insert_query);
        
        foreach ($parsed_items_object as $char_name => $items) {
            foreach($items as $row) {
                $stmt->bindValue(':charName', $char_name, PDO::PARAM_STR);
                $stmt->bindValue(':itemLocation', $row[0], PDO::PARAM_STR);
                $stmt->bindValue(':itemName', $row[1], PDO::PARAM_STR);
                $stmt->bindValue(':itemId', (int)$row[2], PDO::PARAM_INT);
                $stmt->bindValue(':itemCount', (int)$row[3], PDO::PARAM_INT);
                $stmt->bindValue(':fileDate', $row[5], PDO::PARAM_STR);
                $stmt->execute();
            }
        }
        $this->db->commit();
   }
}

