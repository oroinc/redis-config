<?php

namespace Oro\Bundle\RedisConfigBundle\DependencyInjection;

use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Generates service definition for data cache and doctrine orm cache services
 */
trait RedisCacheTrait
{
    protected function getRedisServiceDefinition(ContainerBuilder $container, string $predisService) : Definition
    {
        $cacheDefinition = new Definition(
            RedisAdapter::class,
            [$container->getDefinition($predisService)]
        );
        $cacheDefinition->setAbstract(true);

        return $cacheDefinition;
    }
}
