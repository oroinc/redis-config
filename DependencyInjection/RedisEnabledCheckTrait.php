<?php

namespace Oro\Bundle\RedisConfigBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

trait RedisEnabledCheckTrait
{
    /**
     * @param ContainerBuilder $container
     * @param string $paramName
     * @return bool
     */
    private function validateRedisConfigDsnValue(ContainerBuilder $container, $paramName)
    {
        if (!$container->hasParameter($paramName) || (null === $container->getParameter($paramName))) {
            return false;
        }

        return true;
    }

    /**
     * @param ContainerBuilder $container
     * @return bool
     */
    protected function isRedisEnabledForSessions(ContainerBuilder $container)
    {
        if ($this->validateRedisConfigDsnValue($container, 'redis_dsn_session')
            && self::REDIS_SESSION_HANDLER == $container->getParameter('session_handler')) {
            return true;
        }

        return false;
    }

    /**
     * @param ContainerBuilder $container
     * @return bool
     */
    protected function isRedisEnabledForCache(ContainerBuilder $container)
    {
        if ($this->validateRedisConfigDsnValue($container, 'redis_dsn_cache')) {
            return true;
        }

        return false;
    }

    /**
     * @param ContainerBuilder $container
     * @return bool
     */
    protected function isRedisEnabledForDoctrine(ContainerBuilder $container)
    {
        if ($this->validateRedisConfigDsnValue($container, 'redis_dsn_doctrine')) {
            return true;
        }

        return false;
    }

    /**
     * @param ContainerBuilder $container
     * @return bool
     */
    public function isRedisEnabled(ContainerBuilder $container)
    {
        return $this->isRedisEnabledForSessions($container)
            || $this->isRedisEnabledForCache($container)
            || $this->isRedisEnabledForDoctrine($container);
    }
}
