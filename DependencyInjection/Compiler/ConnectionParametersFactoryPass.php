<?php

namespace Oro\Bundle\RedisConfigBundle\DependencyInjection\Compiler;

use Oro\Bundle\RedisConfigBundle\Factory\PredisParametersFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Changes the factory of redis connection parameters services to fix the bug with 0 database.
 *
 * @see \Oro\Bundle\RedisConfigBundle\Factory\PredisParametersFactory
 */
class ConnectionParametersFactoryPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        foreach ($container->findTaggedServiceIds('snc_redis.connection_parameters') as $id => $attr) {
            $parameterDefinition = $container->getDefinition($id);
            $parameterDefinition->setFactory([PredisParametersFactory::class, 'create']);
        }
    }
}
