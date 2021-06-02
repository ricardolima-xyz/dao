<?php
use PHPUnit\Framework\TestCase;

include_once '../dao.class.php';

final class DAOTest extends TestCase
{
    public function test(): void
    {
        $pdo = new PDO("sqlite:./test.db");
        $this->assertIsObject($pdo);
        
        $cleanDatabase = $pdo->exec("
            DROP TABLE IF EXISTS test_table
        ");
        // $pdo->exec returns false when there is an error
        $this->assertNotFalse($cleanDatabase);

        $createTable = $pdo->exec("
            CREATE TABLE IF NOT EXISTS test_table (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                columnone VARCHAR( 255 ),
                columntwo DATE,
                active INTEGER DEFAULT 1
            )
        ");
        // $pdo->exec returns false when there is an error
        $this->assertNotFalse($createTable);
        
        $vacuumDatabase = $pdo->exec("
            VACUUM
        ");
        // $pdo->exec returns false when there is an error
        $this->assertNotFalse($vacuumDatabase);

        $dao = new DAOforTest();
        $this->assertIsObject($dao);

        // Testing count - Empty database
        $daoCount1 = $dao->count();
        $this->assertEquals(0, $daoCount1);

        // Testing exists - Empty database
        $daoExists1 = $dao->exists('1');
        $this->assertEquals(false, $daoExists1);

        // Testing create
        $daoCreate = $dao->create(['columnone'=>'aaa','columntwo'=>'01-01-2030']);
        $this->assertEquals(1, $daoCreate);

        // Testing count - Database with one element
        $daoCount2 = $dao->count();
        $this->assertEquals(1, $daoCount2);

        // Testing exists - Database with one element
        $daoExists2 = $dao->exists('1');
        $this->assertEquals(true, $daoExists2);

        // Testing get
        $daoGet = $dao->get($daoCreate);
        $this->assertIsArray($daoGet);
        $this->assertEquals($daoCreate, $daoGet['id']);
        $this->assertEquals('aaa', $daoGet['columnone']);
        $this->assertEquals('01-01-2030', $daoGet['columntwo']);
        $this->assertEquals(1, $daoGet['active']);

        // Testing list
        $daoList = $dao->list();
        $this->assertIsArray($daoList);
        $this->assertArrayHasKey($daoCreate, $daoList);
        $this->assertEquals($daoGet, $daoList[$daoCreate]);
        
        // Testing delete
        $daoDelete = $dao->del($daoCreate);
        $this->assertTrue($daoDelete);
        // This DAO deactivates its objects - The object can be retrieved with active = 0
        $daoGetDeactivated = $dao->get($daoCreate);
        $this->assertIsArray($daoGetDeactivated);
        $this->assertEquals($daoCreate, $daoGetDeactivated['id']);
        $this->assertEquals('aaa', $daoGetDeactivated['columnone']);
        $this->assertEquals('01-01-2030', $daoGetDeactivated['columntwo']);
        $this->assertEquals(0, $daoGetDeactivated['active']);
        // This DAO deactivates its objects - The list cannot retrieve deactivated objects
        // in a simple list()
        $daoList = $dao->list();
        $this->assertIsArray($daoList);
        $this->assertArrayNotHasKey($daoCreate, $daoList);

        // Testing another create
        $daoCreate = $dao->create(['columnone'=>'bbb','columntwo'=>'02-01-2030']);
        $this->assertEquals(2, $daoCreate);

        // Testing count - Database with one element (plus one more inactive element)
        $daoCount3 = $dao->count();
        $this->assertEquals(1, $daoCount3);

        // Testing count - considering active and inactive elements
        $daoCount4 = $dao->count([['property' => 'active', 'operator'=>'<', 'value' => 2]]);
        $this->assertEquals(2, $daoCount4);

        // Testing exists - The element is inactive. Can only be retrieved by get.
        $daoExists3 = $dao->exists('1');
        $this->assertEquals(false, $daoExists3);

        // Testing exists - The remaining active element.
        $daoExists4 = $dao->exists('2');
        $this->assertEquals(true, $daoExists4);

        // Multiple queries
        for ($i=0; $i < 10; $i++) {
            $daoCreatex = $dao->create(['columnone'=>microtime(),'columntwo'=>'01-01-2030']);
            $this->assertEquals($i+3, $daoCreatex);
        }

    }
}

class DAOforTest extends DAO {
    function __construct() {
        parent::__construct(
            new PDO("sqlite:./test.db"),
            'test_table',
            [
                'id'            => PDO::PARAM_INT,
                'columnone'     => PDO::PARAM_STR,
                'columntwo'     => PDO::PARAM_STR,
                'active'        => PDO::PARAM_INT
            ],
            'id',
            true,
            [
                DAO::DEL_METHOD             => DAO::DEL_METHOD_DEACTIVATE,
                DAO::DEACTIVATE_PROPERTY    => 'active'
            ]
        );
    }
}
?>