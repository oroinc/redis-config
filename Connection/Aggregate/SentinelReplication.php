<?php

namespace  Oro\Bundle\RedisConfigBundle\Connection\Aggregate;

use Predis\Connection\Aggregate\SentinelReplication as OriginalSentinelReplication;
use Predis\Connection\NodeConnectionInterface;

class SentinelReplication extends OriginalSentinelReplication
{
    /** @var string */
    protected $preferSlave;

    /**
     * Returns a preferred slave.
     *
     * @return NodeConnectionInterface
     */
    protected function pickSlave()
    {
        foreach ($this->getSlaves() as $slave) {
            /** @var \Predis\Connection\NodeConnectionInterface $slave */
            $parameters = $slave->getParameters();
            if ($parameters->host == $this->preferSlave) {
                return $slave;
            }
        }
        return parent::pickSlave();
    }

    /**
     * @param $preferSlave
     * @return SentinelReplication
     */
    public function setPreferSlave($preferSlave)
    {
        $this->preferSlave = $preferSlave;
        return $this;
    }
}
