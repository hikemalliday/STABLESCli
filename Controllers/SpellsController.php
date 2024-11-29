<?php

// $this->db = new PDO("sqlite3:" . $this->dbPath);
namespace STABLESCli\Controllers;

use PDO;
use InvalidArgumentException;
use PDOException;

class SpellsController {
    private $eq_dir;
    private $current_page;
    private $dbPath;
    private $db;
    private $parsed_spells_object;
    private $modified_dates = [];
    private $char_class_map = [];
    private $missing_spells = [];
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

    public function getSpellsQuery(array $kwargs) {
        try {
            $item_name = '%' . strtolower($kwargs['spell_name'] ?? '') . '%';
            $char_name = '%' . strtolower($kwargs['char_name'] ?? '') . '%';
            $page_size = $kwargs['page_size'] ?? 75;
            $offset = $this->current_page * $page_size; 
            $spells_query = "SELECT * 
                            FROM missingSpells 
                            WHERE spellName LIKE :spell_name
                            AND charName LIKE :char_name;
                            LIMIT $page_size OFFSET $offset";

            $stmt = $this->db->prepare($spells_query);
            $stmt->bindParam(':spell_name', $item_name);
            $stmt->bindParam(':char_name', $char_name);

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "getSpells error: " . $e->getMessage() . "\n";
        }
    }

    public function parseSpells()
    {
        echo "Missing spells parse started..." . "\n";
        if (is_dir($this->eq_dir) && ($handle = opendir($this->eq_dir))) {
            while (false !== ($file_name = readdir($handle))) {
                if ($file_name !== '.' && $file_name !== '..') $this->_readSpellsFile($file_name);
            }
            closedir($handle);
            $this->_determineCharClass($this->parsed_spells_object);
            //$this->_insertSpellsObject($this->parsed_spells_object);
            $this->_determineMissingSpells();
            $this->_insertSpellsObject();
        } else {
            echo $this->colors['red'] . "$this->eq_dir is not a valid directory or could not be opened." . $this->colors['reset'] . "\n";
            return;
        }
        echo $this->colors['green'] . "Spells parse complete." . $this->colors['reset'] . "\n";
    }

    private function _readSpellsFile($file_name)
    {
        $pattern = '/^([A-Za-z]+)-Spellbook\.txt$/';
        $file_path = $this->eq_dir . '/' . $file_name;

        if (!(preg_match($pattern, $file_name, $matches))) return;
        if (!is_file($file_path) && !is_readable($file_path)) return;
        $char_name = $matches[1];
        
        $handle = fopen($file_path, 'r');
        $first_line = fgets($handle);
        $this->modified_dates[$char_name] = date("m-d-Y", filemtime($file_path));

        if (count(explode("\t", $first_line)) !== 2) return;
        // Reset handle
        fseek($handle, 0);
        $this->parsed_spells_object[$char_name] = [];

        while (($line = fgets($handle)) !== false) {
            $line_as_array = explode("\t", $line);
            $line_as_array = array_map('trim', $line_as_array);
            $this->parsed_spells_object[$char_name][] = $line_as_array;
        }
       
    }

    private function _determineCharClass($parsed_spells_object)
    {
        $spells_master = json_decode(file_get_contents('spellsMaster.json'), true);
        
        $char_class_temp = [];
        // Loop over entire parsed object
        foreach($parsed_spells_object as $char_name => $spellbook) {
            if (!array_key_exists($char_name, $char_class_temp)) {
                $char_class_temp[$char_name] = [
                    'tally' => [
                        'enchanter' => 0,
                        'mage' => 0,
                        'necromancer' => 0,
                        'wizard' => 0,
                        'cleric' => 0,
                        'druid' => 0,
                        'shaman' => 0,
                        'bard' => 0,
                        'monk' => 0,
                        'ranger' => 0,
                        'rogue' => 0,
                        'paladin' => 0,
                        'shadowknight' => 0,
                        'warrior' => 0,
                    ]
                ];
            }
            // Loop over each row in a players spellbook
            foreach($spellbook as $row) {
                $level = $row[0];
                $spell_name = $row[1];
                // Find the spells in the master object
                foreach($spells_master as $class_name => $class_spells) {
                    // If we find the spell name + correct level in the master object...
                    if (array_key_exists($spell_name, $class_spells) && ($class_spells[$spell_name] == $level)) {
                        $char_class_temp[$char_name]['tally'][$class_name] += 1;
                        // Break out once class is determined
                        // NOTE: We had to go up to 14 because Grixus was matching as Enchanter.
                        // This is bad design, but whatever
                        if ($char_class_temp[$char_name]['tally'][$class_name] >= 14) {
                            $this->char_class_map[$char_name] = $class_name;
                            break;
                        }   
                    }
                }
            }
        }
        return  $this->char_class_map;
    }

    private function _determineMissingSpells()
    {
        foreach($this->parsed_spells_object as $char_name => $char_spells) {
            $spells_master = json_decode(file_get_contents('spellsMaster.json'), true);
            $char_class = $this->char_class_map[$char_name];
            
            foreach($char_spells as $row) {
                $spell_name = $row[1];
                if (array_key_exists($spell_name, $spells_master[$char_class])) unset($spells_master[$char_class][$spell_name]);
            }
            $this->missing_spells[$char_name] = $spells_master[$char_class];
        }
    }

    private function _insertSpellsObject()
    {
        $this->db->beginTransaction();
        $insert_query = "INSERT INTO missingSpells (charName, spellName, level, fileDate) VALUES (:charName, :spellName, :level, :fileDate)";
       
        $stmt = $this->db->prepare($insert_query);
       
        foreach($this->missing_spells as $char_name => $spells) {
            $modified_date = $this->modified_dates[$char_name];
            foreach($spells as $spell_name => $level) {
                $stmt->bindValue(':charName',  $char_name, PDO::PARAM_STR);
                $stmt->bindValue(':spellName', $spell_name, PDO::PARAM_STR);
                $stmt->bindValue(':level', $level, PDO::PARAM_INT);
                $stmt->bindValue(':fileDate', $modified_date, PDO::PARAM_STR);
                $stmt->execute();
            }
        }
        $this->db->commit();
    }

}