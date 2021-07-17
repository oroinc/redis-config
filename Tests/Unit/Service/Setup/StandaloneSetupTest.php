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

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
    }

    /**
     * @dataProvider getConfigDataProvider
     */
    public function testGetConfig($configAlias, $params, $dsnConfig)
    {
        $this->container->setParameter('redis_dsn_' . $configAlias, $dsnConfig);
        $redisSetup = new Setup\StandaloneSetup();
        $redisSetup->setContainer($this->container);
        $input = [$configAlias => $params];
        $output = $redisSetup->getConfig($input, $configAlias);
        $this->assertEquals($dsnConfig, $output['dsn']);
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
                'redis://127.0.0.1:6379/0'
            ],
            [
                'cache',
                ['type' => 'predis', 'alias' => 'cache', 'options' => ['connection_persistent' => true]],
                'redis://127.0.0.1:6379/1'
            ],
            [
                'doctrine',
                ['type' => 'predis', 'alias' => 'doctrine'],
                'redis://127.0.0.1:6379/2'
            ],
        ];
    }
}
