<?php

namespace Oro\Bundle\RedisConfigBundle\Service\Setup;

/**
 * SetupInterface should be implemented by classes that depends on a redis config, redis client.
 */
interface SetupInterface
{
    /**
     * @param array  $config
     * @param string $redisType
     *
     * @return mixed
     */
    public function getConfig(array $config, string $redisType);
}
