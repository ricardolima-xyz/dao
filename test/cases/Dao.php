<?php
use PHPUnit\Framework\TestCase;

include_once __DIR__.'/../../Dao.php';

final class DAOTest extends TestCase
{
    // TODO: Separate DAO TEST INTO DAOs FUNCTIONS
    // TODO Minuciouslly test filter and orderby
    public function testBasicTest(): void
    {
        $pdo = new PDO("sqlite:./test.sqlite");
        $this->assertIsObject($pdo);
        
        $cleanDatabase = $pdo->exec("
            DROP TABLE IF EXISTS test_table
        ");
        // $pdo->exec returns false when there is an error
        $this->assertNotFalse($cleanDatabase);

        $createTable = $pdo->exec("
            CREATE TABLE IF NOT EXISTS test_table (
                id INTEGER PRIMARY KEY,
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

        $dao = new DaoForTest();
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
        
        // Testing update
        $daoUpdate = $dao->update(['id'=>$daoCreate,'columnone'=>'zzz','columntwo'=>'02-02-2030']);

        // Testing updatekey
        $newkey = 2;
        $daoUpdate = $dao->updateKey($daoCreate, $newkey);

        // Testing get after updates
        $daoGetAfterUpdate = $dao->get($newkey);
        $this->assertIsArray($daoGetAfterUpdate);
        $this->assertEquals($newkey, $daoGetAfterUpdate['id']);
        $this->assertEquals('zzz', $daoGetAfterUpdate['columnone']);
        $this->assertEquals('02-02-2030', $daoGetAfterUpdate['columntwo']);
        $this->assertEquals(1, $daoGetAfterUpdate['active']);

        // Testing delete
        $daoDelete = $dao->del($newkey);
        $this->assertTrue($daoDelete);
        // This DAO deactivates its objects. A simple list can't retrieve them,
        // a more complex list can retrieve them and a get can also retrieve them.
        $this->assertEquals(0, $dao->count());
        $this->assertEquals(1, $dao->count([['property' => 'active', 'operator'=>'<', 'value' => 2]]));
        $daoGetDeactivated = $dao->get($newkey);
        $this->assertIsArray($daoGetDeactivated);
        $this->assertEquals($newkey, $daoGetDeactivated['id']);
        $this->assertEquals('zzz', $daoGetDeactivated['columnone']);
        $this->assertEquals('02-02-2030', $daoGetDeactivated['columntwo']);
        $this->assertEquals(0, $daoGetDeactivated['active']);
        // This DAO deactivates its objects - The list cannot retrieve deactivated objects
        // in a simple list()
        $daoList = $dao->list();
        $this->assertIsArray($daoList);
        $this->assertArrayNotHasKey($daoCreate, $daoList);

        // Testing another create
        $daoCreate = $dao->create(['columnone'=>'bbb','columntwo'=>'02-01-2030']);
        $this->assertEquals(3, $daoCreate);

        // Testing count - Database with one element (plus one more inactive element)
        $daoCount3 = $dao->count();
        $this->assertEquals(1, $daoCount3);

        // Testing count - considering active and inactive elements
        $daoCount4 = $dao->count([['property' => 'active', 'operator'=>'<', 'value' => 2]]);
        $this->assertEquals(2, $daoCount4);

        // Testing exists - The element is inactive. Can only be retrieved by get.
        $daoExists3 = $dao->exists('2');
        $this->assertEquals(false, $daoExists3);

        // Testing exists - The remaining active element.
        $daoExists4 = $dao->exists('3');
        $this->assertEquals(true, $daoExists4);

        // Multiple queries
        for ($i=0; $i < 50; $i++) {
            $daoCreatex = $dao->create(['columnone'=>microtime(),'columntwo'=>'01-01-2030']);
            $this->assertEquals($i+4, $daoCreatex);
        }

    }
}

class DaoForTest extends Dao {
    function __construct() {
        parent::__construct(
            new PDO("sqlite:./test.sqlite"),
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