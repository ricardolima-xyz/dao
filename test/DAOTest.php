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

        $daoCreate = $dao->create(['columnone'=>'aaa','columntwo'=>'01-01-2030']);
        $this->assertEquals(1, $daoCreate);

        $daoGet = $dao->get($daoCreate);
        $this->assertIsArray($daoGet);
        $this->assertEquals($daoCreate, $daoGet['id']);
        $this->assertEquals('aaa', $daoGet['columnone']);
        $this->assertEquals('01-01-2030', $daoGet['columntwo']);
        $this->assertEquals(1, $daoGet['active']);

    }
}

class DAOforTest extends DAO {
    function __construct() {
        parent::__construct(
            '',new PDO("sqlite:./test.db"),
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