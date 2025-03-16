<?php

use PHPUnit\Framework\TestCase;
use Ezydb\SqlConverter\SqlConverter;

final class SqlConverterTest extends TestCase
{

    public function testConstructor(): void
    {
        $sqlConverter = new SqlConverter('mysql');
        $this->assertInstanceOf(SqlConverter::class, $sqlConverter);
    }

    // TODO: Add more tests
}