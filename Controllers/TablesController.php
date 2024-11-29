<?php

namespace STABLESCli\Controllers;

use PDO;
use PDOException;


class TablesController {
    static function createTables($db) {
        $queries = [
            "CREATE TABLE IF NOT EXISTS items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                charName TEXT,
                itemLocation TEXT,
                itemName TEXT,
                itemId INTEGER,
                itemCount INTEGER,
                itemSlots INTEGER,
                fileDate TEXT
            )",
            "CREATE TABLE IF NOT EXISTS eqDir (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                eqDir TEXT
            )",
            "CREATE TABLE IF NOT EXISTS missingSpells (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                charName TEXT,
                spellName TEXT,
                level INTEGER,
                fileDate TEXT
            )",
            "CREATE TABLE IF NOT EXISTS yellowText (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                killer TEXT,
                victim TEXT,
                zone TEXT,
                timeStamp TEXT
            )",
            "CREATE TABLE IF NOT EXISTS campOut (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                charName TEXT,
                zone TEXT,
                timeStamp TEXT
            )",
        ];
    
        try {
            $db->beginTransaction();
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
            foreach ($queries as $query) {
                $db->exec($query);
            }
            // Check existing data in eqDir
            $result = $db->query("SELECT * FROM eqDir");

            // Fetch all rows as an array
            $rows = $result->fetchAll(PDO::FETCH_ASSOC);
            foreach ($result as $row) {
                print_r($row);
            }

            if (empty($rows)) {
                // Insert a new row
                $eq_dir_insert = "INSERT INTO eqDir (eqDir) VALUES (:eq_dir)";
                $stmt = $db->prepare($eq_dir_insert);

                $eq_dir_value = "C:/r99/";
                $stmt->bindValue(':eq_dir', $eq_dir_value, PDO::PARAM_STR);
               
                if ($stmt->execute()) {
                    echo "Row inserted successfully into eqDir.\n";
                } else {
                    echo "Insert failed: ";
                    print_r($stmt->errorInfo());
                }
            }
            $db->commit();
    
        } catch (PDOException $e) {
            echo "createTables error: " . $e->getMessage() . "\n";
            $db->rollBack(); // Rollback transaction if error occurs
        }
    }
        static function deleteAllRows($db) {
            $queries = [
                "DELETE FROM items",
                "DELETE FROM missingSpells",
                "DELETE FROM yellowText",
                "DELETE FROM campOut",
            ];
            
            try {
                $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                foreach($queries as $query) $db->exec($query);
            } catch (PDOException $e) {
                echo "deleteAllRows:" . $e->getMessage();
            }
        }

        static function deleteTables($db) {
            $queries = [
                "DROP TABLE items",
                "DROP TABLE eqDir",
                "DROP TABLE missingSpells",
                "DROP TABLE yellowText",
                "DROP TABLE campOut",
            ];

            try {
                $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                foreach($queries as $query) $db->exec($query);
                echo "ALL TABLES DELETED" . "\n";
            } catch (PDOException $e) {
                echo "deleteTables:" . $e->getMessage();
            }
        }
}
                                                