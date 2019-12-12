<?php

namespace Oro\Bundle\RedisConfigBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\RedisConfigBundle\Configuration\Options;
use Oro\Bundle\RedisConfigBundle\DependencyInjection\Compiler\ConfigCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ConfigCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider definitionDataProvider
     */
    public function testConfigPreferSlaveOptions(Definition $clientOptionsDef)
    {
        $argumentValue = '192.168.10.1';
        $preferSlaveParamName = 'redis_cache_sentinel_prefer_slave';
        $ipAddressProviderServiceId = 'oro.redis_config.ip_address_provider';

        $clientOptionsDef->setClass(Options::class);

        $container = new ContainerBuilder();
        $container->setParameter('redis_dsn_cache', 'redis://127.0.0.1:6379/0');
        $container->setDefinition($ipAddressProviderServiceId, new Definition());
        $container->setDefinition('snc_redis.client.cache_options', $clientOptionsDef);
        $container->setParameter($preferSlaveParamName, $argumentValue);

        $configCompilerPass = new ConfigCompilerPass();
        $configCompilerPass->process($container);

        $this->assertEquals($argumentValue, $clientOptionsDef->getArgument(1));
        $this->assertEquals(
            [['setIpAddressProvider', [new Reference($ipAddressProviderServiceId)]]],
            $clientOptionsDef->getMethodCalls()
        );
        $this->assertFalse($container->hasParameter($preferSlaveParamName));
    }

    /**
     * @return array
     */
    public function definitionDataProvider()
    {
        return [
            'no args' => [(new Definition())],
            '1 arg' => [(new Definition())->addArgument([])],
            'more than 1 arg' => [(new Definition())->addArgument([])->addArgument('127.0.0.1')],
        ];
    }

    public function testConfigPreferSlaveOptionsWithSeveralClients()
    {
        $ipAddressProviderServiceId = 'oro.redis_config.ip_address_provider';
        $cacheClientOptionsDef = new Definition();
        $cacheClientOptionsDef->setClass(Options::class);
        $doctrineClientOptionsDef = new Definition();
        $doctrineClientOptionsDef->setClass(Options::class);
        $sessionClientOptionsDef = new Definition();
        $sessionClientOptionsDef->setClass(Options::class);

        $container = new ContainerBuilder();
        $container->setParameter('redis_dsn_cache', 'redis://127.0.0.1:6379/0');
        $container->setDefinition($ipAddressProviderServiceId, new Definition());
        $container->setDefinition('snc_redis.client.cache_options', $cacheClientOptionsDef);
        $container->setParameter('redis_cache_sentinel_prefer_slave', '192.168.10.1');
        $container->setDefinition('snc_redis.client.doctrine_options', $doctrineClientOptionsDef);
        $container->setParameter('redis_doctrine_sentinel_prefer_slave', '192.168.10.2');
        $container->setDefinition('snc_redis.client.session_options', $sessionClientOptionsDef);
        $container->setParameter('redis_session_sentinel_prefer_slave', '192.168.10.3');

        $configCompilerPass = new ConfigCompilerPass();
        $configCompilerPass->process($container);

        $this->assertEquals('192.168.10.1', $cacheClientOptionsDef->getArgument(1));
        $this->assertEquals(
            [['setIpAddressProvider', [new Reference($ipAddressProviderServiceId)]]],
            $cacheClientOptionsDef->getMethodCalls()
        );
        $this->assertFalse($container->hasParameter('redis_cache_sentinel_prefer_slave'));

        $this->assertEquals('192.168.10.2', $doctrineClientOptionsDef->getArgument(1));
        $this->assertEquals(
            [['setIpAddressProvider', [new Reference($ipAddressProviderServiceId)]]],
            $doctrineClientOptionsDef->getMethodCalls()
        );
        $this->assertFalse($container->hasParameter('redis_doctrine_sentinel_prefer_slave'));

        $this->assertEquals('192.168.10.3', $sessionClientOptionsDef->getArgument(1));
        $this->assertEquals(
            [['setIpAddressProvider', [new Reference($ipAddressProviderServiceId)]]],
            $sessionClientOptionsDef->getMethodCalls()
        );
        $this->assertFalse($container->hasParameter('redis_session_sentinel_prefer_slave'));
    }

    public function testConfigSlugCacheWithoutEnabledRedisCache()
    {
        $container = new ContainerBuilder();
        $container->setParameter('oro_redirect.url_cache_type', 'storage');

        $compilerPass = new ConfigCompilerPass();
        $compilerPass->process($container);

        $this->assertEquals('storage', $container->getParameter('oro_redirect.url_cache_type'));
    }

    public function testConfigSlugCacheWithEnabledRedisCache()
    {
        $container = new ContainerBuilder();
        $container->setParameter('oro_redirect.url_cache_type', 'storage');
        $container->setParameter('redis_dsn_cache', 'redis://127.0.0.1:6379/0');

        $compilerPass = new ConfigCompilerPass();
        $compilerPass->process($container);

        $this->assertEquals('key_value', $container->getParameter('oro_redirect.url_cache_type'));
    }
}
