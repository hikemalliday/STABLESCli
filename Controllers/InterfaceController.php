<?php

// $this->db = new PDO("sqlite3:" . $this->dbPath);
namespace STABLESCli\Controllers;

use PDO;
use InvalidArgumentException;
use PDOException;

class InterfaceController {
    private $eq_dir;
    private $current_page;
    private $db_path;
    private $db;
    private $parsed_campout_object;
    private $campout_controller;
    private $items_controller;
    private $spells_controller;
    private $yellowtext_controller;
    private $eq_dir_controller;
    private $page_size;

    private $colors = [
        'red' => "\033[31m",
        'green' => "\033[32m",
        'yellow' => "\033[33m",
        'blue' => "\033[34m",
        'reset' => "\033[0m",
    ];

    public function __construct(array $kwargs) {
       $this->db = $kwargs["db"];
       $this->page_size = 75;
       $eq_dir_controller = new EqDirController(['db' => $this->db]);
       $this->eq_dir = $eq_dir_controller->getEqDir();
       TablesController::createTables($this->db);
       $this->campout_controller = new CampOutController(['db' => $this->db, 'eq_dir' => $this->eq_dir]);
       $this->items_controller = new ItemsController(['db' => $this->db, 'eq_dir' => $this->eq_dir]);
       $this->spells_controller = new SpellsController(['db' => $this->db, 'eq_dir' => $this->eq_dir]);
       $this->yellowtext_controller = new YellowTextController(['db' => $this->db, 'eq_dir' => $this->eq_dir]);
       $this->eq_dir_controller = new EqDirController(['db' => $this->db, 'eq_dir' => $this->eq_dir]);
    }

    public function parseAll(){
        TablesController::deleteAllRows($this->db);
        $this->items_controller->parseItems();
        $this->spells_controller->parseSpells();
        $this->campout_controller->parseCampOut();
        $this->yellowtext_controller->parseYellowText();
    }

    public function getItems(array $kwargs) {
        $char_name = $kwargs['char_name'] ?? "";
        $item_name = $kwargs['item_name'] ?? "";
        $page_size = $kwargs['page_size'] ?? $this->page_size;
        $results = $this->items_controller->getItemsQuery(['char_name' => $char_name, 'item_name' => $item_name, 'page_size' => $page_size]);
        $results_obj = $this->formatData($results);
        $this->handleSubCLI($results_obj);

        #print_r($results_obj);
    }

    public function getSpells(array $kwargs) {
        $char_name = $kwargs['char_name'] ?? "";
        $item_name = $kwargs['spell_name'] ?? "";
        $page_size = $kwargs['page_size'] ?? $this->page_size;
        $results = $this->spells_controller->getSpellsQuery(['char_name' => $char_name, 'spell_name' => $item_name, 'page_size' => $page_size]);
        print_r($results);
    }

    public function getYellowText(array $kwargs) {
        $char_name = $kwargs['char_name'] ?? "";
        $zone_name = $kwargs['zone_name'] ?? "";
        $page_size = $kwargs['page_size'] ?? $this->page_size;;
        $results = $this->yellowtext_controller->getYellowTextQuery(['char_name' => $char_name, 'zone_name' => $zone_name, 'page_size' => $page_size]);
        print_r($results);
    }

    public function getCampOut(array $kwargs) {
        $zone_name = $kwargs['zone_name'] ?? "";
        $char_name = $kwargs['char_name'] ?? "";
        $page_size = $kwargs['page_size'] ?? $this->page_size;;
        $results = $this->campout_controller->getCampOutQuery(['char_name' => $char_name, 'zone_name' => $zone_name, 'page_size' => $page_size]);
        print_r($results);
    }
    public function start() {
        echo "\n";
        echo $this->colors['green'] . "Welcome to the STABLES CLI App!" . $this->colors['reset'] . "\n";
        echo "Current eq directory: " . $this->colors['yellow'] . $this->eq_dir . $this->colors['reset'] . "\n";
        echo "Type" . $this->colors['yellow'] . " 'help'" . $this->colors['reset'] . " to see available commands, or" . $this->colors['yellow'] . " 'exit'" . $this->colors['reset'] . " to quit" . "\n";
    
        while (true) {
            echo "\n> "; // Command prompt
            $input = trim(fgets(STDIN)); // Read user input
    
            if ($input === 'exit') {
                echo "Goodbye!\n";
                break;
            }
    
            switch ($input) {
                case 'help':
                    $this->showHelp();
                    break;
    
                case 'parse-all':
                    $this->parseAll();
                    break;
    
                case (preg_match('/^-i/', $input) ? true : false): // Command starts with 'get-items'
                    $this->handleGetItems( $input);
                    break;
    
                case (preg_match('/^-s/', $input) ? true : false): // Command starts with 'get-spells'
                    $this->handleGetSpells($input);
                    break;
    
                case (preg_match('/^-yt/', $input) ? true : false): // Command starts with 'get-yellowtext'
                    $this->handleGetYellowText($input);
                    break;
    
                case (preg_match('/^-camp/', $input) ? true : false): // Command starts with 'get-campout'
                    $this->handleGetCampOut($input);
                    break;

                case (preg_match('/^-parse/', $input) ? true : false): // Command starts with 'get-campout'
                    $this->handleParse();
                    break;
    
                default:
                    echo "Unknown command: $input. Type 'help' for a list of commands.\n";
            }
        }
    }

    private function formatData($results) {
        $i = 0;
        $page_num = 1;
        $page = [];
        $results_obj = [];
        for ($i = 0; $i < count($results); $i++) {
            // Add the current result to the page
            $page[] = $results[$i];
    
            // If the page is full, add it to the results object and reset
            if (count($page) === $this->page_size) {
                $results_obj[$page_num] = $page;
                $page = [];
                $page_num++; // Move to the next page
            }
        }
        // Add any remaining items in the last page
        if (!empty($page)) {
            $results_obj[$page_num] = $page;
        }
        return $results_obj;
    }

    private function handleSubCLI($results) {
        echo "RESULTS\n";
        print_r($results);
        $page = 1;
        $total_pages = ceil(count($results) / $this->page_size);
    
        // Enable raw input mode
        shell_exec('stty -icanon -echo');
        echo "\nUse LEFT/RIGHT arrows to navigate, ESC to return to main menu.\n";
    
        while (true) {
            // Clear the screen
            echo "\033[H\033[J";
    
            // Display the current page of results
            echo "Page $page of $total_pages\n";
            print_r($results[$page - 1]); // Adjusted for 0-based index
    
            echo "\nUse LEFT/RIGHT arrows to navigate, ESC to return.\n";
    
            // Read user input
            $char = fread(STDIN, 3);
    
            if ($char === "\033") { // Handle ESC key
                $char .= fread(STDIN, 2); // Grab remaining bytes
                if ($char === "\033") { // If it's just ESC
                    break;
                }
            }
    
            if ($char === "\033[C") { // RIGHT arrow
                $page = ($page < $total_pages) ? $page + 1 : 1; // Wrap around to the first page
            } elseif ($char === "\033[D") { // LEFT arrow
                $page = ($page > 1) ? $page - 1 : $total_pages; // Wrap around to the last page
            }
        }
    
        // Restore terminal to normal state
        shell_exec('stty sane');
    }
    
    private function showHelp() {
        echo "Available commands:\n";
        echo "  help               - Show this help message\n";
        echo "  exit               - Exit the application\n";
        echo "  -parse             - Parse all data\n";
        echo "  -i <item name>     - Retrieve items (optional params: -char)\n";
        echo "  -s                 - Retrieve spells (optional params: -char)\n";
        echo "  -yt                - Retrieve yellow text logs (optional params: -char, -zone)\n";
        echo "  -camp              - Retrieve camp out logs (optional params: -char, -zone)\n";
    }
    
    private function handleGetItems($input) {
        $params = $this->getParams($input, 'item');
        $this->getItems($params);
    }
    
    private function handleGetSpells($input) {
        $params = $this->getParams($input);
        $this->getSpells($params);
    }
    
    private function handleGetYellowText($input) {
        $params = $this->getParams($input);
        $this->getYellowText($params);
    }
    
    private function handleGetCampOut($input) {
        $params = $this->getParams($input);
        $this->getCampOut($params);
    }

    private function handleParse() {
        $this->parseAll();
    }

    private function getParams($input, $commandType = '') {
        $params = [];
    
        if ($commandType === 'item') {
            // Match -i with optional item name but stop at other flags
            if (preg_match('/^-i(?:\s+(?!-char|-zone)(\S+))?/', $input, $matches)) {
                $params['item_name'] = $matches[1] ?? ""; // If no item name, default to empty string
            }
        } else if ($commandType === 'spell') {
            // Match -i with optional item name but stop at other flags
            if (preg_match('/^-i(?:\s+(?!-char)(\S+))?/', $input, $matches)) {
                $params['spell_name'] = $matches[1] ?? ""; // If no item name, default to empty string
            }
        } else if ($commandType === 'yt') {
            // Match -i with optional item name but stop at other flags
            if (preg_match('/^-i(?:\s+(?!-char|-zone)(\S+))?/', $input, $matches)) {
                $params['spell_name'] = $matches[1] ?? ""; // If no item name, default to empty string
            }
        } else if ($commandType === 'camp') {
            // Match -i with optional item name but stop at other flags
            if (preg_match('/^-i(?:\s+(?!-char|-zone)(\S+))?/', $input, $matches)) {
                $params['spell_name'] = $matches[1] ?? ""; // If no item name, default to empty string
            }
        }
        // Match -char with optional character name
        if (preg_match('/-zone\s+([^-].*?)(?=\s-|\s*$)/', $input, $matches)) {
            $params['zone_name'] = trim($matches[1]) ?? "";
        } else {
            $params['zone_name'] = ""; // Default to empty string if not provided
        }

        if (preg_match('/-char\s+([^-].*?)(?=\s-|\s*$)/', $input, $matches)) {
            $params['char_name'] = trim($matches[1]) ?? "";
        } else {
            $params['char_name'] = ""; // Default to empty string if not provided
        }
        return $params;
    }
}