<?php

namespace Oro\Bundle\RedisConfigBundle\Configuration;

use Oro\Bundle\RedisConfigBundle\Connection\Aggregate\SentinelReplication;
use Predis\Configuration\OptionsInterface;
use Predis\Configuration\ReplicationOption as BaseReplicationOption;

/**
 * To be able to use a configured preferable slave server for an application, resolve the sentinel option
 * as an instance of {@see \Oro\Bundle\RedisConfigBundle\Connection\Aggregate\SentinelReplication}
 * instead of an instance of {@see \Predis\Connection\Aggregate\SentinelReplication}.
 */
class ReplicationOption extends BaseReplicationOption
{
    /**
     * {@inheritdoc}
     */
    public function filter(OptionsInterface $options, $value)
    {
        if ('sentinel' === $value) {
            return function ($sentinels, Options $options) {
                $sentinelReplication = new SentinelReplication($options->service, $sentinels, $options->connections);
                $sentinelReplication->setPreferSlave($options->getPreferSlave());

                return $sentinelReplication;
            };
        }
    }
}
