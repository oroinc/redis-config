<?php

namespace Oro\Bundle\RedisConfigBundle\Service\Setup;

/**
 * Interface SetupInterface
 * @package Oro\Bundle\RedisConfigBundle\DependencyInjection\Setup
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
