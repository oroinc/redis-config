<?php

namespace Oro\Bundle\RedisConfigBundle\DependencyInjection;

use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Generates service definition for data cache and doctrine orm cache services
 */
trait RedisCacheTrait
{
    protected function getRedisServiceDefinition(ContainerBuilder $container, string $predisService) : Definition
    {
        $doctrineProviderDefinition = new Definition(
            DoctrineProvider::class,
            [new Reference('oro_cache.' . $predisService)]
        );
        $doctrineProviderDefinition->setFactory([DoctrineProvider::class, 'wrap']);

        return $doctrineProviderDefinition;
    }
}
