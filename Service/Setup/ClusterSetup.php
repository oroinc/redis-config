<?php

namespace Oro\Bundle\RedisConfigBundle\Service\Setup;

/**
 * {@inheritdoc}
 */
class ClusterSetup extends AbstractSetup
{
    /** setup type */
    const TYPE = 'cluster';

    /**
     * @param array  $config
     *
     * @return array
     */
    public function getConfig(array $config)
    {
        $redisClient = $this->getRedisClient();

        $dsnParameterValue = $this->container->getParameter(sprintf('redis_dsn_%s', $redisClient));
        $config[$redisClient]['dsn'] = (array) $dsnParameterValue;
        $config[$redisClient]['options']['replication'] = true;

        return $config;
    }
}
