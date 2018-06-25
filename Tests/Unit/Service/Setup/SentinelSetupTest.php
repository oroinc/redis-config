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

    protected function setUp()
    {
        $this->container = new ContainerBuilder();
    }

    /**
     * @dataProvider getConfigDataProvider
     * @param $configAlias
     * @param $params
     * @param $dsnConfig
     * @param $dbIndex
     * @param $sentinelService
     */
    public function testGetConfig($configAlias, $params, $dsnConfig, $dbIndex, $sentinelService)
    {
        $dsnConfigSet = [];
        array_walk($dsnConfig, function ($val, $key) use (&$dsnConfigSet, $dbIndex) {
            $dsnConfigSet[$key] = $val . '/' . $dbIndex;
        });
        $this->container->setParameter('redis_dsn_' . $configAlias, $dsnConfigSet);
        $this->container->setParameter('redis_sentinel_master_name', $sentinelService);
        $redisSetup = new Setup\SentinelSetup();
        $redisSetup->setContainer($this->container);
        $input = [$configAlias => $params];
        $output = $redisSetup->getConfig($input);
        $this->assertEquals($dsnConfig, $output[$configAlias]['dsn']);
        $this->assertEquals(Setup\SentinelSetup::TYPE, $output[$configAlias]['options']['replication']);
        $this->assertEquals($sentinelService, $output[$configAlias]['options']['service']);
        $this->assertEquals($dbIndex, $output[$configAlias]['options']['parameters']['database']);
    }

    /**
     * @return array
     */
    public function getConfigDataProvider()
    {
        return [
            [
                'session',
                ['type' => 'predis', 'alias' => 'session'],
                ['redis://127.0.0.1:26379', 'redis://127.0.0.2:26379'],
                0,
                'mymaster'
            ],
            [
                'cache',
                ['type' => 'predis', 'alias' => 'cache',],
                ['redis://127.0.0.1:26379', 'redis://127.0.0.2:26379'],
                1,
                'mymaster'
            ],
            [
                'doctrine',
                ['type' => 'predis', 'alias' => 'doctrine',],
                ['redis://127.0.0.1:26379', 'redis://127.0.0.2:26379'],
                2,
                'mymaster'
            ],
        ];
    }

    /**
     * @dataProvider incorrectConfigDataProvider
     * @param $dsn
     */
    public function testIncorrectConfig($dsn)
    {
        $input = ['cache' => ['dsn' => $dsn]];
        $this->container->setParameter('redis_dsn_cache', $dsn);
        $this->container->setParameter('redis_sentinel_master_name', 'mymaster');
        $redisSetup = new Setup\SentinelSetup();
        $redisSetup->setContainer($this->container);
        $this->expectException(\InvalidArgumentException::class);
        $redisSetup->getConfig($input);
    }

    /**
     * @return array
     */
    public function incorrectConfigDataProvider()
    {
        return [
            ['redis://127.0.0.1:6379/0'], [['redis://127.0.0.1:6379/0']]
        ];
    }
}
