<?php
namespace STABLESCli\Tests; 

use PDO;
use PHPUnit\Framework\TestCase;
use STABLESCli\Controllers\ItemsController;
use STABLESCli\Controllers\SpellsController;
use STABLESCli\Controllers\YellowTextController;
use STABLESCli\Controllers\CampOutController;
use STABLESCli\Fixtures\LoadFixtures;

final class DatabaseTest extends TestCase 
{
    private $loaded_db;
    private $dbPath;
    private $tables_controller;
    private $items_controller;
    private $spells_controller;
    private $yellow_text_controller;
    private $camp_out_controller;
    private $eq_dir;

    protected function setUp(): void
    {
        $db = new PDO('sqlite::memory:');
		$fixtures_instance = new LoadFixtures();
	    $this->loaded_db = $fixtures_instance->loadFixtures($db);
        $this->items_controller = new ItemsController(['db' => $this->loaded_db]);
        $this->spells_controller = new SpellsController(['db' => $this->loaded_db]);
        $this->yellow_text_controller = new YellowTextController(['db' => $this->loaded_db]);
        $this->camp_out_controller = new CampOutController(['db' => $this->loaded_db]);
    }
    public function testGetItems(): void
    {
        $result = $this->items_controller->getItemsQuery(["item_name" => "Abashi"])[0];
        $this->assertContains('Abashi', $result);
        
        $result = $this->items_controller->getItemsQuery(["item_name" => "Abashi", "char_name" => "Grixus"])[0];
        $this->assertContains("Abashi", $result);
        $this->assertContains("Grixus", $result);

        $results = $this->items_controller->getItemsQuery(["char_name" => "Grixus"]);
        foreach ($results as $result) {
            $this->assertContains("Grixus", $result);
        }
    }
    public function testGetSpells(): void
    {
        $result = $this->spells_controller->getSpellsQuery(["spell_name" => "Sunstrike"])[0];
        $this->assertContains('Sunstrike', $result);
        
        $result = $this->spells_controller->getSpellsQuery(["spell_name" => "Sunstrike", "char_name" => "Stork"])[0];
        $this->assertContains("Sunstrike", $result);
        $this->assertContains("Stork", $result);
    }
    public function testGetYellowText(): void
    {
        $result = $this->yellow_text_controller->getYellowTextQuery(["char_name" => "Grixus"])[0];
        $this->assertContains('Grixus', $result);
        $this->assertContains('Sirban', $result);
    }
    public function testGetCampOut(): void
    {
        $result = $this->camp_out_controller->getCampOutQuery(["char_name" => "Grixus"])[0];
        $this->assertContains('Western Wastes', $result);
    }
    public function testParseItems(): void
    {
        $items_controller = new ItemsController(['db' => $this->loaded_db, 'eq_dir' => './Tests/mockfiles']);
        $items_controller->parseItems();
        // Now we need to query the DB and make assertions
        $result = $items_controller->getItemsQuery(['item_name' => 'Valid Item'])[0];
        $this->assertContains('Valid Item', $result);
    }
    public function testParseSpells(): void
    {
        $spells_controller = new SpellsController(['db' => $this->loaded_db, 'eq_dir' => './Tests/mockfiles']);
        $spells_controller->parseSpells();
        // Now we need to query the DB and make assertions
        $result = $spells_controller->getSpellsQuery(['spell_name' => 'Deflux'])[0];
        $this->assertEmpty($result, 'The query result should not contain "Deflux".');
    }
}