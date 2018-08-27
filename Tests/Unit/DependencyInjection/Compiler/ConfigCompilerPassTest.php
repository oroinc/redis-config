<?php

namespace Oro\Bundle\RedisConfigBundle\Tests\Unit\DependencyInjection\Compiler;

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
        $argumentValue = '~';
        $container = new ContainerBuilder();

        $extension->setClass('Oro\Bundle\RedisConfigBundle\Configuration\Options');

        $container->setDefinition('oro.redis_config.configuration_option', $extension);
        $container->setParameter('redis_sentinel_prefer_slave', $argumentValue);

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
            'no args' => [(new Definition())],
            '1 arg' => [(new Definition())->addArgument([])],
            'more than 1 arg' => [(new Definition())->addArgument([])->addArgument('127.0.0.1')],
        ];
    }

    public function testConfigSlugCache()
    {
        $container = new ContainerBuilder();
        $container->setParameter(ConfigCompilerPass::URL_CACHE_TYPE, ConfigCompilerPass::URL_CACHE_STORAGE);

        $compilerPass = new ConfigCompilerPass();
        $compilerPass->process($container);

        $this->assertEquals(
            ConfigCompilerPass::URL_CACHE_KEY_VALUE,
            $container->getParameter(ConfigCompilerPass::URL_CACHE_TYPE)
        );
    }
}
