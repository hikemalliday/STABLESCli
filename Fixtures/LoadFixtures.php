<?php
namespace STABLESCli\Fixtures;
require_once __DIR__ . '/../vendor/autoload.php';
use PDO;
use PDOException;
use STABLESCli\Controllers\TablesController;
final class LoadFixtures
{

    private $mock_items;
    private $mock_missing_spells;
    private $mock_yellow_text;
    private $mock_camp_out;
    private $items_insert;
    private $spells_insert;
    private $yellow_text_insert;
    private $camp_out_insert;

    public function __construct() 
    {
        $this->mock_items =  
        [
            [
                "charName" => "Grixus",
                "itemLocation" => "Inventory",
                "itemName" => "Abashi",
                "itemId" => "100",
                "itemCount" => "1",
                "itemSlots" => "1",
                "fileDate" => "date"
            ],
            [
                "charName" => "Grixus",
                "itemLocation" => "Inventory",
                "itemName" => "Eye of Cazic-Thule",
                "itemId" => "100",
                "itemCount" => "1",
                "itemSlots" => "1",
                "fileDate" => "date"
            ],
            [
                "charName" => "Grixus",
                "itemLocation" => "Inventory",
                "itemName" => "Silver Charm of Tranquility",
                "itemId" => "100",
                "itemCount" => "1",
                "itemSlots" => "1",
                "fileDate" => "date"
            ],
            [
                "charName" => "Grixus",
                "itemLocation" => "Inventory",
                "itemName" => "Barrier of Sound",
                "itemId" => "100",
                "itemCount" => "1",
                "itemSlots" => "1",
                "fileDate" => "date"
            ],
            [
                "charName" => "Grixus",
                "itemLocation" => "Inventory",
                "itemName" => "Zlandicar's Heart",
                "itemId" => "100",
                "itemCount" => "1",
                "itemSlots" => "1",
                "fileDate" => "date"
            ],
        ];
        $this->mock_missing_spells =  
        [
            [
                "charName" => "Stork",
                "spellName" => "Sunstrike",
                "level" => 60
            ],
            [
                "charName" => "Stork",
                "spellName" => "Lure of Ice",
                "level" => 60
            ],
            [
                "charName" => "Stork",
                "spellName" => "Gate",
                "level" => 60
            ],
            [
                "charName" => "Stork",
                "spellName" => "Invisibility",
                "level" => 60
            ],
            [
                "charName" => "Stork",
                "spellName" => "Ice Comet",
                "level" => 60
            ],
        ];
        $this->mock_yellow_text = 
        [
            [
                "charName" => "Grixus",
                "victim" => "Sirban",
                "zone" => "Western Wastes",
                "timeStamp" => "date",
            ], 
            [
                "charName" => "Grixus",
                "victim" => "Tomb",
                "zone" => "Plane of Mischief",
                "timeStamp" => "date",
            ], 
            [
                "charName" => "Grixus",
                "victim" => "Suna",
                "zone" => "Wakening Lands",
                "timeStamp" => "date",
            ], 
            [
                "charName" => "Grixus",
                "victim" => "Pucca",
                "zone" => "Cobalt Scar",
                "timeStamp" => "date",
            ], 
            [
                "charName" => "Grixus",
                "victim" => "Xeek",
                "zone" => "Western Wastes",
                "timeStamp" => "date",
            ], 
        ];  
        $this->mock_camp_out = 
        [
            [
                "charName" => "Grixus",
                "zone" => "Western Wastes",
                "timeStamp" => "date",
            ], 
            [
                "charName" => "Grixus",
                "zone" => "Plane of Mischief",
                "timeStamp" => "date",
            ], 
            [
                "charName" => "Stork",
                "zone" => "Wakening Lands",
                "timeStamp" => "date",
            ], 
            [
                "charName" => "Captainn",
                "zone" => "Cobalt Scar",
                "timeStamp" => "date",
            ], 
            [
                "charName" => "Lifesaver",
                "zone" => "Western Wastes",
                "timeStamp" => "date",
            ], 
        ];

        $this->items_insert = "INSERT INTO items 
            (charName, itemLocation, itemName, itemId, itemCount, itemSlots, fileDate) 
            VALUES (:charName, :itemLocation, :itemName, :itemId, :itemCount, :itemSlots, :fileDate)";
        $this->spells_insert = "INSERT INTO missingSpells (charName, spellName, level) 
            VALUES (:charName, :spellName, :level)";
        $this->yellow_text_insert = "INSERT INTO yellowText (charName, victim, zone, timeStamp) 
            VALUES (:charName, :victim, :zone, :timeStamp)";
        $this->camp_out_insert = "INSERT INTO campOut (charName, zone, timeStamp) 
             VALUES (:charName, :zone, :timeStamp)";
    }

    public function loadFixtures(PDO $db) 
    {
        try {
            TablesController::createTables($db);
            $this->_loadItemsFixture($db);
            $this->_loadSpellsFixture($db);
            $this->_loadYellowTextFixture($db);
            $this->_loadCampOutFixture($db);
            echo "Fixtures loaded." . PHP_EOL;
            return $db;
        } catch (PDOException $e) {
            echo "loadFixtures error:" . $e->getMessage();
        } 
    }
    private function _loadItemsFixture(PDO $db)
    {
        $stmt = $db->prepare($this->items_insert);

        foreach ($this->mock_items as $item) {
            $stmt->bindParam(':charName', $item['charName']);
            $stmt->bindParam(':itemLocation', $item['itemLocation']);
            $stmt->bindParam(':itemName', $item['itemName']);
            $stmt->bindParam(':itemId', $item['itemId']);
            $stmt->bindParam(':itemCount', $item['itemCount']);
            $stmt->bindParam(':itemSlots', $item['itemSlots']);
            $stmt->bindParam(':fileDate', $item['fileDate']);
            $stmt->execute($item);
        }
    }
    private function _loadSpellsFixture(PDO $db)
    {

        $stmt = $db->prepare($this->spells_insert);

        foreach ($this->mock_missing_spells as $spell) {
            $stmt->bindParam(':charName', $spell['charName']);
            $stmt->bindParam(':spellName', $spell['spellName']);
            $stmt->bindParam(':level', $spell['level']);
            $stmt->execute($spell); 
        }
    }
    private function _loadYellowTextFixture(PDO $db)
    {
        $stmt = $db->prepare($this->yellow_text_insert);

        foreach ($this->mock_yellow_text as $entry) {
            $stmt->bindParam(':charName', $entry['charName']);
            $stmt->bindParam(':victim', $entry['victim']);
            $stmt->bindParam(':zone', $entry['zone']);
            $stmt->bindParam(':timeStamp', $entry['timeStamp']);
            $stmt->execute($entry);
        }
    }
    private function _loadCampOutFixture(PDO $db)
    {
        $stmt = $db->prepare($this->camp_out_insert);

        foreach ($this->mock_camp_out as $entry) {
            $stmt->bindParam(':charName', $entry['charName']);
            $stmt->bindParam(':zone', $entry['zone']);
            $stmt->bindParam(':timeStamp', $entry['timeStamp']);
            $stmt->execute($entry);
        }
    }
}


