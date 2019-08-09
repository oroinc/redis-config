<?php

namespace Oro\Bundle\RedisConfigBundle\DependencyInjection\Compiler;

use Oro\Bundle\RedisConfigBundle\Configuration\Options;
use Oro\Bundle\RedisConfigBundle\DependencyInjection\RedisEnabledCheckTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Configure services for redis usage.
 */
class ConfigCompilerPass implements CompilerPassInterface
{
    use RedisEnabledCheckTrait;

    const URL_CACHE_TYPE      = 'oro_redirect.url_cache_type';
    const URL_CACHE_STORAGE   = 'storage';
    const URL_CACHE_KEY_VALUE = 'key_value';

    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$this->isRedisEnabled($container)) {
            return;
        }

        foreach ($container->getDefinitions() as $definition) {
            if ($definition->getClass() === Options::class) {
                $types = ['cache', 'doctrine', 'session'];
                foreach ($types as $type) {
                    if (false === $container->hasParameter(sprintf('redis_%s_sentinel_prefer_slave', $type))) {
                        continue;
                    }

                    $argsCount = \count($definition->getArguments());
                    if (!$argsCount) {
                        $definition->addArgument([]);
                        $argsCount++;
                    }
                    if ($argsCount === 1) {
                        $definition->addArgument(
                            $container->getParameter(
                                sprintf('redis_%s_sentinel_prefer_slave', $type)
                            )
                        );
                    } else {
                        $definition->replaceArgument(
                            1,
                            $container->getParameter(
                                sprintf('redis_%s_sentinel_prefer_slave', $type)
                            )
                        );
                    }
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
        if ($this->isRedisEnabledForCache($container)
            && $container->hasParameter(self::URL_CACHE_TYPE)
            && $container->getParameter(self::URL_CACHE_TYPE) === self::URL_CACHE_STORAGE
        ) {
            $container->setParameter(self::URL_CACHE_TYPE, self::URL_CACHE_KEY_VALUE);
        }
    }
}
