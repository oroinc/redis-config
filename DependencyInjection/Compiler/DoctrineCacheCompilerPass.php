<?php

namespace Oro\Bundle\RedisConfigBundle\DependencyInjection\Compiler;

use Oro\Bundle\CacheBundle\DependencyInjection\Compiler\CacheConfigurationPass as CacheConfiguration;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Configure Doctrine related caches.
 */
class DoctrineCacheCompilerPass implements CompilerPassInterface
{
    private const DOCTRINE_CACHE_SERVICE           = 'oro.doctrine.abstract';
    private const DOCTRINE_CACHE_NO_MEMORY_SERVICE = 'oro.doctrine.abstract.without_memory_cache';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($this->isRedisEnabledForDoctrine($container)) {
            $abstractCacheDef = $container->getDefinition(self::DOCTRINE_CACHE_SERVICE);
            $container->setDefinition(
                self::DOCTRINE_CACHE_SERVICE,
                CacheConfiguration::getMemoryCacheChain($abstractCacheDef)
            );
            $container->setDefinition(
                self::DOCTRINE_CACHE_NO_MEMORY_SERVICE,
                $abstractCacheDef
            );
            foreach ($this->getDoctrineCacheServices($container) as $serviceId) {
                $serviceDef = $container->getDefinition($serviceId);
                if ($serviceDef instanceof ChildDefinition
                    && strpos($serviceDef->getParent(), CacheConfiguration::DATA_CACHE_SERVICE) === 0
                ) {
                    $newServiceDef = new ChildDefinition(
                        str_replace(
                            CacheConfiguration::DATA_CACHE_SERVICE,
                            self::DOCTRINE_CACHE_SERVICE,
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
}
