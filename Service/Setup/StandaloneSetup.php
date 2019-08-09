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
     * @param array  $config
     * @param string $redisType
     *
     * @return array
     */
    public function getConfig(array $config, string $redisType)
    {
        $dsnParameterValue = $this->container->getParameter(sprintf('redis_dsn_%s', $redisType));
        $config[$redisType]['dsn'] = (string) $dsnParameterValue;

        return $config[$redisType];
    }
}
