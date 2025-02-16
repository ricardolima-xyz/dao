<?php
use PHPUnit\Framework\TestCase;

include_once __DIR__.'/../../Dao.php';

final class DAOTest extends TestCase
{
    private $testPdo;
    private $testDao;

    protected function setUp(): void
    {
        $this->testPdo = new PDO("sqlite::memory:");
        $this->testPdo->exec("
            DROP TABLE IF EXISTS testEntity
        ");
        $this->testPdo->exec("
            CREATE TABLE IF NOT EXISTS testEntity (
                id INTEGER PRIMARY KEY,
                columnone VARCHAR(255),
                columntwo INTEGER,
                columnthree DOUBLE,
                active INTEGER DEFAULT 1
            )
        ");
        $this->testDao = new DAO(
            $this->testPdo,
            'testEntity',
            [
                'id'            => PDO::PARAM_INT,
                'columnone'     => PDO::PARAM_STR,
                'columntwo'     => PDO::PARAM_INT,
                'columnthree'   => PDO::PARAM_STR,
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

    public function countProvider(): array
    {
        return [
            'null filter' => [null, 4],
            'empty filter' => [[], 6],
            'equals to operator' => [[['property' => 'active', 'operator'=>'=', 'value' => 0]], 2],
            'diffrent from operator' => [[['property' => 'columnone', 'operator'=>'<>', 'value' => 'value3']], 5],
            'greater than operator' => [[['property' => 'columnthree', 'operator'=>'>', 'value' => 500]], 1],
            'like operator' => [[['property' => 'columnone', 'operator'=>'LIKE', 'value' => '%value%']], 6],
            'is null operator' => [[['property' => 'columntwo', 'operator'=>'IS NULL']], 2],
            'is not null operator' => [[['property' => 'columntwo', 'operator'=>'IS NOT NULL']], 4],
        ];
    }
    /**
     * @dataProvider countProvider
     */
    public function testCount($filters, $expectedResult): void
    {
        $this->testPdo->exec("DELETE FROM testEntity");
        $this->testPdo->exec("INSERT INTO testEntity 
                (columnone, columntwo, columnthree, active) VALUES
                ('value1', 01, '2.6', 1),
                ('value2', NULL, '0.0', 0),
                ('value3', 90, '999', 1),
                ('value4', NULL, '234', 0),
                ('value5', 23, '6.0', 1),
                ('value6', 12, '2.6', 1)
        ");
        $result = $this->testDao->count($filters);
        $this->assertEquals($expectedResult, $result);
    }

    public function testCreate(): void
    {
        $testEntity = [
            'columnone'=>'value1',
            'columntwo'=>42,
            'columnthree'=>null,
            'active'=>0
        ];
        $testEntity['id'] = $this->testDao->create($testEntity);
        $this->assertNotNull($testEntity['id']);
        $retrievedTestEntity = $this->testPdo->query("SELECT * FROM testEntity WHERE id = {$testEntity['id']}")->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals($testEntity, $retrievedTestEntity);
    }

    public function testDel(): void
    {
        $testEntity = [
            'columnone'=>'value1',
            'columntwo'=>42,
            'columnthree'=>null,
            'active'=>0
        ];
        $testEntity['id'] = $this->testDao->create($testEntity);
        $retrievedTestEntity = $this->testPdo->query("SELECT * FROM testEntity WHERE id = {$testEntity['id']} AND active = '1'")->fetch(PDO::FETCH_ASSOC);
        $this->assertNotNull($retrievedTestEntity);
        $this->testDao->del($testEntity['id']);
        $retrievedTestEntity = $this->testPdo->query("SELECT * FROM testEntity WHERE id = {$testEntity['id']} AND active = '1'")->fetch(PDO::FETCH_ASSOC);
        $this->assertFalse($retrievedTestEntity);
    }

    public function escProvider(): array
    {
        return [
            'single identifier' => ['identifier', '"identifier"'],
            'single asterisk' => ['*', '*'],
            'two identifiers' => ['identifier1.identifier2', '"identifier1"."identifier2"'],
            'three identifiers' => ['identifier1.identifier2.identifier3', '"identifier1"."identifier2"."identifier3"'],
            'identifier with asterisk' => ['identifier1.*', '"identifier1".*']
        ];
    }
    /**
     * @dataProvider escProvider
     */
    public function testEsc($input, $expectedResult): void
    {
        $result = $this->testDao->esc($input);
        $this->assertEquals($expectedResult, $result);
    }

    public function existsProvider(): array
    {
        return [
            'something that exists' => [1, true],
            'something that does not exist' => [2, false]
        ];
    }
    /**
     * @dataProvider existsProvider
     */
    public function testExists($input, $expectedResult): void
    {
        $this->testPdo->exec("DELETE FROM testEntity");
        $this->testPdo->exec("INSERT INTO testEntity 
                (id, columnone, columntwo, columnthree, active) VALUES
                (1, 'value1', 01, '2.6', 1)
        ");
        $result = $this->testDao->exists($input);
        $this->assertEquals($expectedResult, $result);
    }

    public function testGet(): void
    {
        $testEntity = [
            'columnone'=>'value1',
            'columntwo'=>null,
            'columnthree'=>3.45,
            'active'=>1
        ];
        $testEntity['id'] = $this->testDao->create($testEntity);
        $result = $this->testDao->get($testEntity['id']);
        $expectedResult = $testEntity;
        $this->assertEquals($expectedResult, $result);
    }

    public function testList(): void
    {
    }

    public function testUpdate(): void
    {
    }

    public function testUpdateKey(): void
    {
    }
}