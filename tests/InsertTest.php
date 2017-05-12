<?php

namespace FOD\DBALClickHouse\Tests;


use FOD\DBALClickHouse\Connection;
use PHPUnit\Framework\TestCase;

class InsertTest extends TestCase
{
    /** @var  Connection */
    protected $connection;

    public function setUp()
    {
        $this->connection = CreateConnectionTest::createConnection();

        $fromSchema = $this->connection->getSchemaManager()->createSchema();
        $toSchema = clone $fromSchema;

        $newTable = $toSchema->createTable('test_insert_table');

        $newTable->addColumn('id', 'integer', ['unsigned' => true]);
        $newTable->addColumn('payload', 'string');
        $newTable->addOption('engine', 'Memory');
        $newTable->setPrimaryKey(['id']);

        $generatedSQL = implode(';', $fromSchema->getMigrateToSql($toSchema, $this->connection->getDatabasePlatform()));
        $this->connection->exec($generatedSQL);
    }

    public function tearDown()
    {
        $this->connection->exec('DROP TABLE test_insert_table');
    }

    public function testExecInsert()
    {
        $this->connection->exec("INSERT INTO test_insert_table(id, payload) VALUES (1, 'v1'), (2, 'v2')");
        $this->assertEquals([['payload' => 'v1'], ['payload' => 'v2']], $this->connection->fetchAll("SELECT payload from test_insert_table WHERE id IN (1, 2) ORDER BY id"));
    }

    public function testFunctionInsert()
    {
        $this->connection->insert('test_insert_table', ['id' => 3, 'payload' => 'v3']);
        $this->connection->insert('test_insert_table', ['id' => 4, 'payload' => 'v4'], ['id' => \PDO::PARAM_INT, 'payload' => \PDO::PARAM_STR]);
        $this->assertEquals([['payload' => 'v3'], ['payload' => 'v4']], $this->connection->fetchAll("SELECT payload from test_insert_table WHERE id IN (3, 4) ORDER BY id"));
    }

    public function testInsertViaQueryBuilder()
    {
        $qb = $this->connection->createQueryBuilder();

        $qb
            ->insert('test_insert_table')
            ->setValue('id', ':id')
            ->setValue('payload', ':payload')
            ->setParameter('id', 5, \PDO::PARAM_INT)
            ->setParameter('payload', 'v5')
            ->execute();

        $qb
            ->setParameter('id', 6)
            ->setParameter('payload', 'v6')
            ->execute();

        $this->assertEquals([['payload' => 'v5'], ['payload' => 'v6']], $this->connection->fetchAll("SELECT payload from test_insert_table WHERE id IN (5, 6) ORDER BY id"));
    }
}