<?php

namespace Oro\Bundle\RedisConfigBundle\Tests\Unit\Service\Setup;

use Oro\Bundle\RedisConfigBundle\Service\Setup;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class StandaloneSetupTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getConfigDataProvider
     */
    public function testGetConfig(string $configAlias, array $params, string $dsnConfig): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('redis_dsn_' . $configAlias, $dsnConfig);

        $redisSetup = new Setup\StandaloneSetup();
        $redisSetup->setContainer($container);

        $output = $redisSetup->getConfig([$configAlias => $params], $configAlias);

        $this->assertEquals($dsnConfig, $output['dsn']);
    }

    public function getConfigDataProvider(): array
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
