<?php

namespace Oro\Bundle\RedisConfigBundle\DependencyInjection;

use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Generates service definition for data cache and doctrine orm cache services
 */
trait RedisCacheTrait
{
    protected function getRedisServiceDefinition(ContainerBuilder $container, string $predisService) : Definition
    {
        $cacheDefinition = $container->getDefinition('oro_cache.' . $predisService);
        $cacheDefinition->setAbstract(true);
        $doctrineProviderDefinition = new Definition(DoctrineProvider::class, [$cacheDefinition]);
        $doctrineProviderDefinition->setFactory([DoctrineProvider::class, 'wrap']);

        return $doctrineProviderDefinition;
    }
}
