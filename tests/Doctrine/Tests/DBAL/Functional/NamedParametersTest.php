<?php

namespace Doctrine\Tests\DBAL\Functional\Ticket;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Schema\Table;
use const CASE_LOWER;
use function array_change_key_case;

/**
 * @group DDC-1372
 */
class NamedParametersTest extends \Doctrine\Tests\DbalFunctionalTestCase
{
    public function ticketProvider()
    {
        return [
            [
                'SELECT * FROM ddc1372_foobar f WHERE f.foo = :foo AND f.bar IN (:bar)',
                [
                    'foo' => 1,
                    'bar' => [1, 2, 3],
                ],
                [
                    'foo' => ParameterType::INTEGER,
                    'bar' => Connection::PARAM_INT_ARRAY,
                ],
                [
                    ['id' => 1, 'foo' => 1, 'bar' => 1],
                    ['id' => 2, 'foo' => 1, 'bar' => 2],
                    ['id' => 3, 'foo' => 1, 'bar' => 3],
                ],
            ],

            [
                'SELECT * FROM ddc1372_foobar f WHERE f.foo = :foo AND f.bar IN (:bar)',
                [
                    'foo' => 1,
                    'bar' => [1, 2, 3],
                ],
                [
                    'bar' => Connection::PARAM_INT_ARRAY,
                    'foo' => ParameterType::INTEGER,
                ],
                [
                    ['id' => 1, 'foo' => 1, 'bar' => 1],
                    ['id' => 2, 'foo' => 1, 'bar' => 2],
                    ['id' => 3, 'foo' => 1, 'bar' => 3],
                ],
            ],

            [
                'SELECT * FROM ddc1372_foobar f WHERE f.bar IN (:bar) AND f.foo = :foo',
                [
                    'foo' => 1,
                    'bar' => [1, 2, 3],
                ],
                [
                    'bar' => Connection::PARAM_INT_ARRAY,
                    'foo' => ParameterType::INTEGER,
                ],
                [
                    ['id' => 1, 'foo' => 1, 'bar' => 1],
                    ['id' => 2, 'foo' => 1, 'bar' => 2],
                    ['id' => 3, 'foo' => 1, 'bar' => 3],
                ],
            ],

            [
                'SELECT * FROM ddc1372_foobar f WHERE f.bar IN (:bar) AND f.foo = :foo',
                [
                    'foo' => 1,
                    'bar' => ['1', '2', '3'],
                ],
                [
                    'bar' => Connection::PARAM_STR_ARRAY,
                    'foo' => ParameterType::INTEGER,
                ],
                [
                    ['id' => 1, 'foo' => 1, 'bar' => 1],
                    ['id' => 2, 'foo' => 1, 'bar' => 2],
                    ['id' => 3, 'foo' => 1, 'bar' => 3],
                ],
            ],

            [
                'SELECT * FROM ddc1372_foobar f WHERE f.bar IN (:bar) AND f.foo IN (:foo)',
                [
                    'foo' => ['1'],
                    'bar' => [1, 2, 3, 4],
                ],
                [
                    'bar' => Connection::PARAM_STR_ARRAY,
                    'foo' => Connection::PARAM_INT_ARRAY,
                ],
                [
                    ['id' => 1, 'foo' => 1, 'bar' => 1],
                    ['id' => 2, 'foo' => 1, 'bar' => 2],
                    ['id' => 3, 'foo' => 1, 'bar' => 3],
                    ['id' => 4, 'foo' => 1, 'bar' => 4],
                ],
            ],

            [
                'SELECT * FROM ddc1372_foobar f WHERE f.bar IN (:bar) AND f.foo IN (:foo)',
                [
                    'foo' => 1,
                    'bar' => 2,
                ],
                [
                    'bar' => ParameterType::INTEGER,
                    'foo' => ParameterType::INTEGER,
                ],
                [
                    ['id' => 2, 'foo' => 1, 'bar' => 2],
                ],
            ],

            [
                'SELECT * FROM ddc1372_foobar f WHERE f.bar = :arg AND f.foo <> :arg',
                ['arg' => '1'],
                [
                    'arg' => ParameterType::STRING,
                ],
                [
                    ['id' => 5, 'foo' => 2, 'bar' => 1],
                ],
            ],

            [
                'SELECT * FROM ddc1372_foobar f WHERE f.bar NOT IN (:arg) AND f.foo IN (:arg)',
                [
                    'arg' => [1, 2],
                ],
                [
                    'arg' => Connection::PARAM_INT_ARRAY,
                ],
                [
                    ['id' => 3, 'foo' => 1, 'bar' => 3],
                    ['id' => 4, 'foo' => 1, 'bar' => 4],
                ],
            ],
        ];
    }

    protected function setUp()
    {
        parent::setUp();

        if (! $this->_conn->getSchemaManager()->tablesExist('ddc1372_foobar')) {
            try {
                $table = new Table('ddc1372_foobar');
                $table->addColumn('id', 'integer');
                $table->addColumn('foo', 'string');
                $table->addColumn('bar', 'string');
                $table->setPrimaryKey(['id']);

                $sm = $this->_conn->getSchemaManager();
                $sm->createTable($table);

                $this->_conn->insert('ddc1372_foobar', [
                    'id'  => 1,
                    'foo' => 1,
                    'bar' => 1,
                ]);
                $this->_conn->insert('ddc1372_foobar', [
                    'id'  => 2,
                    'foo' => 1,
                    'bar' => 2,
                ]);
                $this->_conn->insert('ddc1372_foobar', [
                    'id'  => 3,
                    'foo' => 1,
                    'bar' => 3,
                ]);
                $this->_conn->insert('ddc1372_foobar', [
                    'id'  => 4,
                    'foo' => 1,
                    'bar' => 4,
                ]);
                $this->_conn->insert('ddc1372_foobar', [
                    'id'  => 5,
                    'foo' => 2,
                    'bar' => 1,
                ]);
                $this->_conn->insert('ddc1372_foobar', [
                    'id'  => 6,
                    'foo' => 2,
                    'bar' => 2,
                ]);
            } catch(\Exception $e) {
                $this->fail($e->getMessage());
            }
        }
    }

    /**
     * @dataProvider ticketProvider
     * @param string $query
     * @param array  $params
     * @param array  $types
     * @param array  $expected
     */
    public function testTicket($query,$params,$types,$expected)
    {
        $stmt   = $this->_conn->executeQuery($query, $params, $types);
        $result = $stmt->fetchAll(FetchMode::ASSOCIATIVE);

        foreach ($result as $k => $v) {
            $result[$k] = array_change_key_case($v, CASE_LOWER);
        }

        self::assertEquals($expected, $result);
    }

    public function namedParametersAfterEscapeProvider()
    {
        return [
            [['param_0' => 'bar', 'param_1' => 'foo', 'find' => '%a%']],
            [['param_0' => 'bar', 'param_1' => 'foo', 'find' => '%o%']],
        ];
    }

    /**
     * @dataProvider namedParametersAfterEscapeProvider
     * @param array $params
     */
    public function testNamedParametersAfterEscape(array $params)
    {
        $escapeChar = $this->_conn->getDatabasePlatform()->quoteStringLiteral('\\');
        $query = str_replace('{{escape}}', $escapeChar, "SELECT 1 FROM ddc1372_foobar WHERE (:param_0 LIKE :find ESCAPE {{escape}}) OR (:param_1 LIKE :find ESCAPE {{escape}}) LIMIT 1");

        $stmt = $this->_conn->executeQuery(
            $query,
            $params,
            ['param_0' => 2, 'param_1' => 2, 'find' => 2]
        );
        $result = $stmt->fetchAll(FetchMode::COLUMN);

        self::assertEquals([
            1,
        ], $result);
    }
}
