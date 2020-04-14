<?php

namespace Oro\Bundle\RedisConfigBundle\Tests\Unit\Service\Setup;

use Oro\Bundle\RedisConfigBundle\Service\Setup;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ClusterSetupTest extends \PHPUnit\Framework\TestCase
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
     * @param $configAlias
     * @param $params
     * @param $dsnConfig
     */
    public function testGetConfig($configAlias, $params, $dsnConfig)
    {
        $this->container->setParameter('redis_dsn_' . $configAlias, $dsnConfig);
        $redisSetup = new Setup\ClusterSetup();
        $redisSetup->setContainer($this->container);
        $input = [$configAlias => $params];
        $output = $redisSetup->getConfig($input, $configAlias);
        $this->assertEquals($dsnConfig, $output[$configAlias]['dsn']);
        $this->assertTrue($output[$configAlias]['options']['replication']);
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
                ['redis://127.0.0.1:6379/0?alias=master', 'redis://127.0.0.1:6380/0']
            ],
            [
                'cache',
                ['type' => 'predis', 'alias' => 'cache', 'options' => ['connection_persistent' => true]],
                ['redis://127.0.0.1:6379/1?alias=master', 'redis://127.0.0.1:6380/1']
            ],
            [
                'doctrine',
                ['type' => 'predis', 'alias' => 'doctrine',],
                ['redis://127.0.0.1:6379/2?alias=master', 'redis://127.0.0.1:6380/2']
            ],
        ];
    }
}
