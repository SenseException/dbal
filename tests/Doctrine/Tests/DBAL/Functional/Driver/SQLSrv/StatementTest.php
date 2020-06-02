<?php

namespace Doctrine\Tests\DBAL\Functional\Driver\SQLSrv;

use Doctrine\DBAL\Driver\SQLSrv\Driver;
use Doctrine\DBAL\Driver\SQLSrv\SQLSrvException;
use Doctrine\DBAL\Logging\EchoSQLLogger;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Tests\DbalFunctionalTestCase;
use function extension_loaded;

class StatementTest extends DbalFunctionalTestCase
{
    protected function setUp() : void
    {
        if (! extension_loaded('sqlsrv')) {
            self::markTestSkipped('sqlsrv is not installed.');
        }

        parent::setUp();

        if ($this->connection->getDriver() instanceof Driver) {
            return;
        }

        self::markTestSkipped('sqlsrv only test');
    }

    public function testFailureToPrepareResultsInException() : void
    {
        // use the driver connection directly to avoid having exception wrapped
        $stmt = $this->connection->getWrappedConnection()->prepare('');

        // it's impossible to prepare the statement without bound variables for SQL Server,
        // so the preparation happens before the first execution when variables are already in place
        $this->expectException(SQLSrvException::class);
        $stmt->execute();
    }

    public function testThis() : void
    {
        $table = new Table('stmt_test');
        $table->addColumn('id', 'integer');
        $table->addColumn('name', 'text', ['notnull' => false]);
        $this->connection->getSchemaManager()->dropAndCreateTable($table);

        $this->connection->insert('stmt_test', [
            'id'     => 1,
            'name'   => 'ignored',
        ], [
            ParameterType::INTEGER,
            ParameterType::STRING,
        ]);

        $this->connection->getConfiguration()->setSQLLogger(new EchoSQLLogger());

        $this->connection->fetchAll('select * from stmt_test where name = ?', ['ignored'], [ParameterType::STRING]);

        $this->fail();
    }
}
