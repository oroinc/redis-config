<?php

namespace Oro\Bundle\RedisConfigBundle\Tests\Unit\Factory;

use Oro\Bundle\RedisConfigBundle\Factory\PredisParametersFactory;
use Predis\Connection\Parameters;

class PredisParametersFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateException()
    {
        $this->expectException(\InvalidArgumentException::class);
        PredisParametersFactory::create([], \stdClass::class, 'redis://localhost');
    }

    /**
     * @param string $dsn
     * @param array  $options
     * @param array  $expectedParameters
     *
     * @dataProvider createDp
     */
    public function testCreate($dsn, $options, $expectedParameters)
    {
        $parameters = PredisParametersFactory::create($options, Parameters::class, $dsn);

        self::assertInstanceOf(Parameters::class, $parameters);

        foreach ($expectedParameters as $name => $value) {
            self::assertEquals($value, $parameters->{$name}, "Wrong '$name' value");
        }

        // No user can exist within a redis connection.
        self::assertObjectNotHasAttribute('user', $parameters);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array[]
     */
    public function createDp(): array
    {
        return [
            [
                'redis://z:df577d779b4f724c8c2@ec2-34-321-123-45.us-east-1.compute.amazonaws.com:3210',
                [
                    'test'      => 123,
                    'some'      => 'string',
                    'arbitrary' => true,
                    'values'    => [1, 2, 3],
                ],
                [
                    'test'               => 123,
                    'some'               => 'string',
                    'arbitrary'          => true,
                    'values'             => [1, 2, 3],
                    'scheme'             => 'tcp',
                    'host'               => 'ec2-34-321-123-45.us-east-1.compute.amazonaws.com',
                    'port'               => 3210,
                    'persistent'         => null,
                    'password'           => 'df577d779b4f724c8c2',
                    'database'           => null,
                ],
            ],
            [
                'redis://pw@/var/run/redis/redis-1.sock/10',
                [
                    'test'     => 124,
                    'password' => 'toto',
                    'alias'    => 'one_alias',
                ],
                [
                    'test'               => 124,
                    'scheme'             => 'unix',
                    'host'               => '127.0.0.1',
                    'port'               => 6379,
                    'path'               => '/var/run/redis/redis-1.sock',
                    'alias'              => 'one_alias',
                    'persistent'         => null,
                    'password'           => 'pw',
                    'database'           => 10,
                ],
            ],
            [
                'rediss://pw@localhost:6380',
                [],
                [
                    'scheme'   => 'tls',
                    'host'     => 'localhost',
                    'port'     => 6380,
                    'password' => 'pw',
                ],
            ],
            [
                'redis://localhost?alias=master',
                ['replication' => true],
                [
                    'scheme'      => 'tcp',
                    'host'        => 'localhost',
                    'port'        => 6379,
                    'replication' => true,
                    'password'    => null,
                    'weight'      => null,
                    'alias'       => 'master',
                    'timeout'     => null,
                ],
            ],
            [
                'redis://localhost?alias=connection_alias',
                [
                    'replication' => true,
                    'alias'       => 'client_alias',
                ],
                [
                    'scheme'      => 'tcp',
                    'host'        => 'localhost',
                    'port'        => 6379,
                    'replication' => true,
                    'password'    => null,
                    'weight'      => null,
                    'alias'       => 'connection_alias',
                    'timeout'     => null,
                ],
            ],
            [
                'redis://localhost/0',
                [
                    'persistent' => true,
                ],
                [
                    'persistent' => 'db0',
                    'database'   => 0,
                ],
            ],
            [
                'redis://localhost/1',
                [
                    'persistent' => true,
                ],
                [
                    'persistent' => 'db1',
                    'database'   => 1,
                ],
            ],
            [
                'redis://localhost/2',
                [
                    'persistent' => true,
                ],
                [
                    'persistent' => 'db2',
                    'database'   => 2,
                ],
            ],
            [
                'redis://localhost/0',
                [
                    'persistent' => false,
                ],
                [
                    'persistent' => false,
                    'database'   => 0,
                ],
            ],
            [
                'redis://localhost/1',
                [
                    'persistent' => false,
                ],
                [
                    'persistent' => false,
                    'database'   => 1,
                ],
            ],
            [
                'redis://localhost/2',
                [
                    'persistent' => false,
                ],
                [
                    'persistent' => false,
                    'database'   => 2,
                ],
            ],
            [
                'tcp://localhost/0',
                [
                    'persistent' => true,
                ],
                [
                    'persistent' => 'db0',
                    'database'   => 0,
                ],
            ],
            [
                'tcp://localhost/0',
                [
                    'persistent' => false,
                ],
                [
                    'persistent' => false,
                    'database'   => 0,
                ],
            ],
            [
                'redis://pw@/var/run/redis/redis-1.sock/0',
                [
                    'persistent' => true,
                ],
                [
                    'persistent' => true,
                    'database'   => 0,
                ],
            ],
            [
                'redis://pw@/var/run/redis/redis-1.sock/0',
                [
                    'persistent' => false,
                ],
                [
                    'persistent' => false,
                    'database'   => 0,
                ],
            ],
        ];
    }
}
