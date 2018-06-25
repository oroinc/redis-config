<?php

namespace Oro\Bundle\RedisConfigBundle\Tests\Unit\Service\Setup;

use Oro\Bundle\RedisConfigBundle\Service\Setup;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class StandaloneSetupTest extends \PHPUnit\Framework\TestCase
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
     */
    public function testGetConfig($configAlias, $params, $dsnConfig)
    {
        $this->container->setParameter('redis_dsn_' . $configAlias, $dsnConfig);
        $redisSetup = new Setup\StandaloneSetup();
        $redisSetup->setContainer($this->container);
        $input = [$configAlias => $params];
        $output = $redisSetup->getConfig($input);
        $this->assertEquals($dsnConfig, $output[$configAlias]['dsn']);
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
                'redis://127.0.0.1:6379/0'
            ],
            [
                'cache',
                ['type' => 'predis', 'alias' => 'cache',],
                'redis://127.0.0.1:6379/1'
            ],
            [
                'doctrine',
                ['type' => 'predis', 'alias' => 'doctrine',],
                'redis://127.0.0.1:6379/2'
            ],
        ];
    }
}
