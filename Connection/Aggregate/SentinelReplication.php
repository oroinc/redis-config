<?php

namespace Oro\Bundle\RedisConfigBundle\Connection\Aggregate;

use Predis\Connection\Aggregate\SentinelReplication as BaseSentinelReplication;
use Predis\Connection\NodeConnectionInterface;

/**
 * Adds support of the preferable slave feature that allows to configure a preferable slave server for an application.
 */
class SentinelReplication extends BaseSentinelReplication
{
    /** @var string|null */
    protected $preferSlave;

    /**
     * Sets IP address or hostname of a preferable slave server.
     *
     * @param string|null $preferSlave
     *
     * @return SentinelReplication
     */
    public function setPreferSlave($preferSlave)
    {
        $this->preferSlave = $preferSlave;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function pickSlave()
    {
        $slave = $this->pickPreferredSlave();
        if (null === $slave) {
            $slave = parent::pickSlave();
        }

        return $slave;
    }

    /**
     * {@inheritdoc}
     */
    protected function assertConnectionRole(NodeConnectionInterface $connection, $role)
    {
        // do not validate the connection role for read-only operations
        // because both the master server and the slave server are valid for these operations,
        // read-only operations may be sent to the master server because a preferable slave
        // can be configured to hit the master server
        if ('slave' === strtolower($role)) {
            return;
        }

        parent::assertConnectionRole($connection, $role);
    }

    /**
     * Returns a preferable slave.
     *
     * @return NodeConnectionInterface|null
     */
    private function pickPreferredSlave()
    {
        if ($this->preferSlave) {
            foreach ($this->getSlaves() as $slave) {
                if ($this->isPreferredSlave($slave)) {
                    return $slave;
                }
            }
            $master = $this->getMaster();
            if ($this->isPreferredSlave($master)) {
                return $master;
            }
        }

        return null;
    }

    /**
     * Checks whether the given connection represents a configured preferable slave.
     *
     * @param NodeConnectionInterface $connection
     *
     * @return bool
     */
    private function isPreferredSlave(NodeConnectionInterface $connection)
    {
        return $connection->getParameters()->host === $this->preferSlave;
    }
}
