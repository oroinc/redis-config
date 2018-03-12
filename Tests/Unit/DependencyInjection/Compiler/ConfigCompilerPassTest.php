<?php

namespace Oro\Bundle\RedisConfigBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\RedisConfigBundle\DependencyInjection\Compiler\ConfigCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ConfigCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcess()
    {
        $argumentValue = '~';
        $container = new ContainerBuilder();
        $extension = new Definition();
        $configCompilerPass = new ConfigCompilerPass();
        $extension->setClass('Oro\Bundle\RedisConfigBundle\Configuration\Options');
        $container->setDefinition('oro.redis_config.configuration_option', $extension);
        $container->setParameter('redis_sentinel_prefer_slave', $argumentValue);
        $configCompilerPass->process($container);
        $this->assertEquals(
            $argumentValue,
            $container->getDefinition('oro.redis_config.configuration_option')->getArgument(0)
        );
    }
}
