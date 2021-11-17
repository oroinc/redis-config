<?php

namespace Oro\Bundle\RedisConfigBundle\DependencyInjection;

use Doctrine\Common\Cache\Psr6\DoctrineProvider;
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
        $doctrineProviderDefinition = new Definition(DoctrineProvider::class, [$cacheDefinition]);
        $doctrineProviderDefinition->setFactory([DoctrineProvider::class, 'wrap']);

        return $doctrineProviderDefinition;
    }
}
