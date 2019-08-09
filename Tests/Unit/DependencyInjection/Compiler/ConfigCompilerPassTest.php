<?php

namespace Oro\Bundle\RedisConfigBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\RedisConfigBundle\Configuration\Options;
use Oro\Bundle\RedisConfigBundle\DependencyInjection\Compiler\ConfigCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ConfigCompilerPassTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider definitionDataProvider
     *
     * @param Definition $extension
     */
    public function testProcess(Definition $extension)
    {
        $argumentValue = '127.0.0.1';
        $container = new ContainerBuilder();

        $extension->setClass(Options::class);


        $container->setDefinition('oro.redis_config.configuration_option', $extension);
        $container->setParameter('redis_cache_sentinel_prefer_slave', $argumentValue);
        $container->setParameter('redis_dsn_cache', 'redis://127.0.0.1:6379/0');

        $configCompilerPass = new ConfigCompilerPass();
        $configCompilerPass->process($container);


        $this->assertEquals(
            $argumentValue,
            $container->getDefinition('oro.redis_config.configuration_option')->getArgument(1)
        );
    }

    /**
     * @return array
     */
    public function definitionDataProvider()
    {
        return [
            'no args'         => [(new Definition())],
            '1 arg'           => [(new Definition())->addArgument([])],
            'more than 1 arg' => [(new Definition())->addArgument([])->addArgument('127.0.0.1')],
        ];
    }

    public function testConfigSlugCacheWithoutEnabledRedisCache()
    {
        $container = new ContainerBuilder();
        $container->setParameter(ConfigCompilerPass::URL_CACHE_TYPE, ConfigCompilerPass::URL_CACHE_STORAGE);

        $compilerPass = new ConfigCompilerPass();
        $compilerPass->process($container);

        $this->assertEquals(
            ConfigCompilerPass::URL_CACHE_STORAGE,
            $container->getParameter(ConfigCompilerPass::URL_CACHE_TYPE)
        );
    }

    public function testConfigSlugCacheWithEnabledRedisCache()
    {
        $container = new ContainerBuilder();
        $container->setParameter(ConfigCompilerPass::URL_CACHE_TYPE, ConfigCompilerPass::URL_CACHE_STORAGE);
        $container->setParameter('redis_dsn_cache', 'redis://127.0.0.1:6379/0');

        $compilerPass = new ConfigCompilerPass();
        $compilerPass->process($container);

        $this->assertEquals(
            ConfigCompilerPass::URL_CACHE_KEY_VALUE,
            $container->getParameter(ConfigCompilerPass::URL_CACHE_TYPE)
        );
    }
}
