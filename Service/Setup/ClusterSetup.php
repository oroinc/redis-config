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
     * @param string $redisType
     *
     * @return array
     */
    public function getConfig(array $config, string $redisType)
    {
        $dsnParameterValue = $this->container->getParameter(sprintf('redis_dsn_%s', $redisType));
        $config[$redisType]['dsn'] = (array) $dsnParameterValue;
        $config[$redisType]['options']['replication'] = true;

        return $config;
    }
}
