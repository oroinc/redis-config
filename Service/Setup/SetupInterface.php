<?php

namespace Oro\Bundle\RedisConfigBundle\Service\Setup;

/**
 * Interface SetupInterface
 * @package Oro\Bundle\RedisConfigBundle\Service\Setup
 */
interface SetupInterface
{
    /**
     * @param array $config
     *
     * @return mixed
     */
    public function getConfig(array $config);
}
