<?php

namespace Oro\Bundle\RedisConfigBundle\DependencyInjection\Compiler;

use Oro\Bundle\CacheBundle\DependencyInjection\Compiler\CacheConfigurationPass;
use Oro\Bundle\RedisConfigBundle\Configuration\Options;
use Oro\Bundle\RedisConfigBundle\DependencyInjection\RedisCacheTrait;
use Oro\Bundle\RedisConfigBundle\DependencyInjection\RedisEnabledCheckTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Configure services for redis usage.
 */
class ConfigCompilerPass implements CompilerPassInterface
{
    use RedisEnabledCheckTrait;
    use RedisCacheTrait;

    private const IP_ADDRESS_PROVIDER_SERVICE_ID     = 'oro.redis_config.ip_address_provider';
    private const CLIENT_OPTIONS_SERVICE_ID_TEMPLATE = 'snc_redis.client.%s_options';
    private const PREFER_SLAVE_PARAM_NAME_TEMPLATE   = 'redis_%s_sentinel_prefer_slave';
    public const SNC_REDIS_CACHE_SERVICE_ID = 'snc_redis.cache';

    private const URL_CACHE_TYPE      = 'oro_redirect.url_cache_type';
    private const URL_CACHE_STORAGE   = 'storage';
    private const URL_CACHE_KEY_VALUE = 'key_value';

    public function process(ContainerBuilder $container)
    {
        if (!$this->isRedisEnabled($container)) {
            return;
        }

        $this->configPreferSlaveOptions($container);
        $this->configSlugCache($container);
        $this->configDataCache($container);
        $this->configClientServices($container);
    }

    private function configPreferSlaveOptions(ContainerBuilder $container): void
    {
        $types = ['cache', 'doctrine', 'session'];
        foreach ($types as $type) {
            $preferSlaveParamName = sprintf(self::PREFER_SLAVE_PARAM_NAME_TEMPLATE, $type);
            if (!$container->hasParameter($preferSlaveParamName)) {
                continue;
            }
            $clientOptionsServiceId = sprintf(self::CLIENT_OPTIONS_SERVICE_ID_TEMPLATE, $type);
            if (!$container->hasDefinition($clientOptionsServiceId)) {
                continue;
            }
            $clientOptionsDef = $container->getDefinition($clientOptionsServiceId);
            if ($clientOptionsDef->getClass() !== Options::class) {
                continue;
            }

            // inject IP address provider
            $clientOptionsDef->addMethodCall(
                'setIpAddressProvider',
                [new Reference(self::IP_ADDRESS_PROVIDER_SERVICE_ID)]
            );
            // inject the preferSlave option
            $clientOptionsDef->addMethodCall(
                'setPreferSlave',
                [$container->getParameter($preferSlaveParamName)]
            );
            // remove "redis_*_sentinel_prefer_slave" parameter that is unneeded anymore
            $container->getParameterBag()->remove($preferSlaveParamName);
        }
    }

    private function configSlugCache(ContainerBuilder $container): void
    {
        if ($this->isRedisEnabledForCache($container)
            && $container->hasParameter(self::URL_CACHE_TYPE)
            && $container->getParameter(self::URL_CACHE_TYPE) === self::URL_CACHE_STORAGE
        ) {
            $container->setParameter(self::URL_CACHE_TYPE, self::URL_CACHE_KEY_VALUE);
        }
    }

    private function configDataCache(ContainerBuilder $container) : void
    {
        if ($this->isRedisEnabledForCache($container)) {
            $redisCache = $this->getRedisServiceDefinition($container, self::SNC_REDIS_CACHE_SERVICE_ID);
            $container->setDefinition(CacheConfigurationPass::DATA_CACHE_NO_MEMORY_SERVICE, $redisCache);
            $container->setDefinition(
                CacheConfigurationPass::DATA_CACHE_SERVICE,
                CacheConfigurationPass::getMemoryCacheChain($redisCache)
            );
        }
    }

    /**
     * Configure client services as public in order to have them persistent
     */
    private function configClientServices(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds('snc_redis.client') as $id => $attr) {
            $clientDefinition = $container->getDefinition($id);
            $clientDefinition->setPublic(true);
        }
    }
}
