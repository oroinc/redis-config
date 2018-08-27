<?php

namespace Oro\Bundle\RedisConfigBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Oro\Bundle\RedisConfigBundle\Configuration\Options;

/**
 * Configure services for redis usage.
 */
class ConfigCompilerPass implements CompilerPassInterface
{
    const URL_CACHE_TYPE = 'oro_redirect.url_cache_type';
    const URL_CACHE_STORAGE = 'storage';
    const URL_CACHE_KEY_VALUE = 'key_value';

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        foreach ($container->getDefinitions() as $definition) {
            if ($definition->getClass() === Options::class && $container->hasParameter('redis_sentinel_prefer_slave')) {
                $argsCount = \count($definition->getArguments());
                if (!$argsCount) {
                    $definition->addArgument([]);
                    $argsCount++;
                }
                if ($argsCount === 1) {
                    $definition->addArgument($container->getParameter('redis_sentinel_prefer_slave'));
                } else {
                    $definition->replaceArgument(1, $container->getParameter('redis_sentinel_prefer_slave'));
                }
            }
        }

        $this->configSlugCache($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function configSlugCache(ContainerBuilder $container)
    {
        if ($container->hasParameter(self::URL_CACHE_TYPE)
            && $container->getParameter(self::URL_CACHE_TYPE) === self::URL_CACHE_STORAGE
        ) {
            $container->setParameter(self::URL_CACHE_TYPE, self::URL_CACHE_KEY_VALUE);
        }
    }
}
