<?php

namespace Oro\Bundle\RedisConfigBundle\DependencyInjection\Compiler;

use Oro\Bundle\CacheBundle\Provider\MemoryCacheChain;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;

/**
 * Configure Doctrine related caches.
 */
class DoctrineCacheCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($this->isRedisEnabledForDoctrine($container)) {
            $abstractCacheDef = $container->getDefinition('oro.doctrine.abstract');
            $container->setDefinition(
                'oro.doctrine.abstract',
                $this->getMemoryCacheChain($abstractCacheDef)
            );
            $container->setDefinition(
                'oro.doctrine.abstract.without_memory_cache',
                $abstractCacheDef
            );
            foreach ($this->getDoctrineCacheServices($container) as $serviceId) {
                $serviceDef = $container->getDefinition($serviceId);
                if ($serviceDef instanceof DefinitionDecorator
                    && strpos($serviceDef->getParent(), 'oro.cache.abstract') === 0
                ) {
                    $newServiceDef = new DefinitionDecorator(
                        str_replace(
                            'oro.cache.abstract',
                            'oro.doctrine.abstract',
                            $serviceDef->getParent()
                        )
                    );
                    $newServiceDef->setArguments($serviceDef->getArguments());
                    $newServiceDef->setProperties($serviceDef->getProperties());
                    $newServiceDef->setMethodCalls($serviceDef->getMethodCalls());
                    $newServiceDef->setPublic($serviceDef->isPublic());
                    $container->setDefinition($serviceId, $newServiceDef);
                }
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return bool
     */
    private function isRedisEnabledForDoctrine(ContainerBuilder $container)
    {
        return
            $container->hasParameter('redis_dsn_doctrine')
            && null !== $container->getParameter('redis_dsn_doctrine');
    }

    /**
     * @param ContainerBuilder $container
     *
     * @return string[]
     */
    private function getDoctrineCacheServices(ContainerBuilder $container)
    {
        $services = [];
        foreach ($container->getExtensionConfig('doctrine') as $config) {
            if (!empty($config['orm']['entity_managers'])) {
                foreach ($config['orm']['entity_managers'] as $emName => $emConfig) {
                    $key = 'orm|' . $emName;
                    $this->processCacheDriver($services, $key, $emConfig, 'metadata_cache_driver');
                    $this->processCacheDriver($services, $key, $emConfig, 'query_cache_driver');
                }
            }
        }

        return array_unique(array_values($services));
    }

    /**
     * @param array  $services
     * @param string $key
     * @param array  $config
     * @param string $driverType
     */
    private function processCacheDriver(&$services, $key, $config, $driverType)
    {
        if (isset($config[$driverType])) {
            $serviceType = $key . '|' . $driverType;
            if ($this->isCacheDriverService($config[$driverType])) {
                $services[$serviceType] = $config[$driverType]['id'];
            } else {
                unset($services[$serviceType]);
            }
        }
    }

    /**
     * @param mixed $driver
     *
     * @return bool
     */
    private function isCacheDriverService($driver)
    {
        return
            is_array($driver)
            && isset($driver['type'])
            && !$driver['type'] !== 'service'
            && !empty($driver['id']);
    }

    /**
     * @param Definition $cacheProvider
     *
     * @return Definition
     */
    private function getMemoryCacheChain(Definition $cacheProvider)
    {
        $definition = new Definition(
            MemoryCacheChain::class,
            [[$cacheProvider]]
        );
        $definition->setAbstract(true);

        return $definition;
    }
}
