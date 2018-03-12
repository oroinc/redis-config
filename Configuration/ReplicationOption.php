<?php

namespace Oro\Bundle\RedisConfigBundle\Configuration;

use Oro\Bundle\RedisConfigBundle\Connection\Aggregate\SentinelReplication;
use Predis\Configuration\OptionsInterface;
use Predis\Configuration\ReplicationOption as OriginalReplicationOption;
use Predis\Connection\Aggregate\MasterSlaveReplication;
use Predis\Connection\Aggregate\ReplicationInterface;

class ReplicationOption extends OriginalReplicationOption
{
    /**
     * Initiate OroReplication service instead of original
     *
     *
     * @param OptionsInterface $options
     * @param mixed $value
     * @return \Closure|mixed|null|MasterSlaveReplication
     */
    public function filter(OptionsInterface $options, $value)
    {
        if ($value instanceof ReplicationInterface) {
            return $value;
        }

        if (is_bool($value) || $value === null) {
            return $value ? $this->getDefault($options) : null;
        }

        if ($value === 'sentinel') {
            return function ($sentinels, $options) {
                $sentinelReplication = new SentinelReplication($options->service, $sentinels, $options->connections);
                return $sentinelReplication->setPreferSlave($options->getPreferSlave());
            };
        }

        if (!is_object($value) &&
            null !== $asbool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
        ) {
            return $asbool ? $this->getDefault($options) : null;
        }

        throw new \InvalidArgumentException(
            "An instance of type 'Predis\Connection\Aggregate\ReplicationInterface' was expected."
        );
    }
}
