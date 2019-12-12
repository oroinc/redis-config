<?php

namespace Oro\Bundle\RedisConfigBundle\DependencyInjection\Compiler;

use Oro\Bundle\RedisConfigBundle\Configuration\Options;
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

    private const IP_ADDRESS_PROVIDER_SERVICE_ID     = 'oro.redis_config.ip_address_provider';
    private const CLIENT_OPTIONS_SERVICE_ID_TEMPLATE = 'snc_redis.client.%s_options';
    private const PREFER_SLAVE_PARAM_NAME_TEMPLATE   = 'redis_%s_sentinel_prefer_slave';

    private const URL_CACHE_TYPE      = 'oro_redirect.url_cache_type';
    private const URL_CACHE_STORAGE   = 'storage';
    private const URL_CACHE_KEY_VALUE = 'key_value';

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$this->isRedisEnabled($container)) {
            return;
        }

        $this->configPreferSlaveOptions($container);
        $this->configSlugCache($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    private function configPreferSlaveOptions(ContainerBuilder $container)
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

    /**
     * @param ContainerBuilder $container
     */
    private function configSlugCache(ContainerBuilder $container)
    {
        if ($this->isRedisEnabledForCache($container)
            && $container->hasParameter(self::URL_CACHE_TYPE)
            && $container->getParameter(self::URL_CACHE_TYPE) === self::URL_CACHE_STORAGE
        ) {
            $container->setParameter(self::URL_CACHE_TYPE, self::URL_CACHE_KEY_VALUE);
        }
    }
}
