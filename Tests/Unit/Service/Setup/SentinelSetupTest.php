<?php

namespace Oro\Bundle\RedisConfigBundle\Tests\Unit\Service\Setup;

use Oro\Bundle\RedisConfigBundle\Service\Setup;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SentinelSetupTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getConfigDataProvider
     */
    public function testGetConfig(
        string $configAlias,
        array $params,
        array $dsnConfig,
        int $dbIndex,
        string $sentinelService
    ): void {
        $dsnConfigSet = [];
        array_walk($dsnConfig, function ($val, $key) use (&$dsnConfigSet, $dbIndex) {
            $dsnConfigSet[$key] = $val . '/' . $dbIndex;
        });

        $container = new ContainerBuilder();
        $container->setParameter('redis_dsn_' . $configAlias, $dsnConfigSet);
        $container->setParameter('redis_' . $configAlias . '_sentinel_master_name', $sentinelService);

        $redisSetup = new Setup\SentinelSetup();
        $redisSetup->setContainer($container);

        $output = $redisSetup->getConfig([$configAlias => $params], $configAlias);

        $this->assertEquals($dsnConfig, $output['dsn']);
        $this->assertEquals(Setup\SentinelSetup::TYPE, $output['options']['replication']);
        $this->assertEquals($sentinelService, $output['options']['service']);
        $this->assertEquals($dbIndex, $output['options']['parameters']['database']);
    }

    public function getConfigDataProvider(): array
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
    public function testIncorrectConfig(string $configAlias, string $dsn, $redisType): void
    {
        $input = ['cache' => ['dsn' => $dsn]];

        $container = new ContainerBuilder();
        $container->setParameter(sprintf('redis_dsn_%s', $redisType), $dsn);
        $container->setParameter(sprintf('redis_%s_sentinel_master_name', $redisType), 'mymaster');

        $redisSetup = new Setup\SentinelSetup();
        $redisSetup->setContainer($container);

        $this->expectException(\InvalidArgumentException::class);

        $redisSetup->getConfig($input, $configAlias);
    }

    public function incorrectConfigDataProvider(): array
    {
        return [
            [
                'cache',
                'redis://127.0.0.1:6379/0',
                'cache',
            ],
            [
                'cache',
                'redis://127.0.0.1:6379/0',
                'cache',
            ],
            [
                'cache',
                'http://127.0.0.1:6379/0',
                'invalid_redis_type'
            ],
            [
                'invalid_config_key',
                'http://127.0.0.1:6379/0',
                'cache'
            ]
        ];
    }
}
