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

        // Testing create
        $daoCreate = $dao->create(['columnone'=>'aaa','columntwo'=>'01-01-2030']);
        $this->assertEquals(1, $daoCreate);

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