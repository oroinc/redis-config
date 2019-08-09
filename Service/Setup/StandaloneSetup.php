<?php

namespace Oro\Bundle\RedisConfigBundle\Service\Setup;

/**
 * {@inheritdoc}
 */
class StandaloneSetup extends AbstractSetup
{
    /** setup type */
    const TYPE = 'standalone';

    /**
     * @param array $config
     *
     * @return array
     */
    public function getConfig(array $config)
    {
        $redisClient = $this->getRedisClient();

        $dsnParameterValue = $this->container->getParameter(sprintf('redis_dsn_%s', $redisClient));
        $config[$redisClient]['dsn'] = (string) $dsnParameterValue;

        return $config[$redisClient];
    }
}
