<?php

namespace Oro\Bundle\RedisConfigBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class ConfigCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        foreach ($container->getDefinitions() as $definition) {
            if ($definition->getClass() == 'Oro\Bundle\RedisConfigBundle\Configuration\Options') {
                $definition->addArgument($container->getParameter('redis_sentinel_prefer_slave'));
            }
        }
    }
}
