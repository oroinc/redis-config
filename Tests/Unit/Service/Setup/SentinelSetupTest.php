<?php

namespace Oro\Bundle\RedisConfigBundle\Tests\Unit\Service\Setup;

use Oro\Bundle\RedisConfigBundle\Service\Setup;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SentinelSetupTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContainerBuilder
     */
    protected $container;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
    }

    /**
     * @dataProvider getConfigDataProvider
     */
    public function testGetConfig($configAlias, $params, $dsnConfig, $dbIndex, $sentinelService)
    {
        $dsnConfigSet = [];
        array_walk($dsnConfig, function ($val, $key) use (&$dsnConfigSet, $dbIndex) {
            $dsnConfigSet[$key] = $val . '/' . $dbIndex;
        });
        $this->container->setParameter('redis_dsn_' . $configAlias, $dsnConfigSet);
        $this->container->setParameter('redis_' . $configAlias . '_sentinel_master_name', $sentinelService);
        $redisSetup = new Setup\SentinelSetup();
        $redisSetup->setContainer($this->container);
        $input = [$configAlias => $params];
        $output = $redisSetup->getConfig($input, $configAlias);
        $this->assertEquals($dsnConfig, $output['dsn']);
        $this->assertEquals(Setup\SentinelSetup::TYPE, $output['options']['replication']);
        $this->assertEquals($sentinelService, $output['options']['service']);
        $this->assertEquals($dbIndex, $output['options']['parameters']['database']);
    }

    /**
     * @return array
     */
    public function getConfigDataProvider()
    {
        return [
            [
                'session',
                ['type' => 'predis', 'alias' => 'session', 'options' => ['connection_persistent' => true]],
                ['redis://127.0.0.1:26379', 'redis://127.0.0.2:26379'],
                0,
                'mymaster'
            ],
            [
                'cache',
                ['type' => 'predis', 'alias' => 'cache', 'options' => ['connection_persistent' => true]],
                ['redis://127.0.0.1:26379', 'redis://127.0.0.2:26379'],
                1,
                'mymaster'
            ],
            [
                'doctrine',
                ['type' => 'predis', 'alias' => 'doctrine'],
                ['redis://127.0.0.1:26379', 'redis://127.0.0.2:26379'],
                2,
                'mymaster'
            ],
        ];
    }

    /**
     * @dataProvider incorrectConfigDataProvider
     */
    public function testIncorrectConfig($configAlias, $dsn)
    {
        $input = ['cache' => ['dsn' => $dsn]];
        $this->container->setParameter('redis_dsn_cache', $dsn);
        $this->container->setParameter('redis_cache_sentinel_master_name', 'mymaster');
        $redisSetup = new Setup\SentinelSetup();
        $redisSetup->setContainer($this->container);
        $this->expectException(\InvalidArgumentException::class);
        $redisSetup->getConfig($input, $configAlias);
    }

    /**
     * @return array
     */
    public function incorrectConfigDataProvider()
    {
        return [
            [
                'cache',
                'redis://127.0.0.1:6379/0'
            ],
            [
                'cache',
                'redis://127.0.0.1:6379/0'
            ]
        ];
    }
}
