<?php

namespace Oro\Bundle\RedisConfigBundle\Configuration;

use Predis\Configuration\Options as BaseOptions;

/**
 * Adds the preferSlave option that allows to configure a preferable slave server for an application.
 */
class Options extends BaseOptions
{
    private const DEFAULT_IP_ADDRESS = '127.0.0.1';

    /** @var string|array|null */
    private $preferSlave;

    /** @var IpAddressProvider */
    private $ipAddressProvider;

    public function getPreferSlave(): string
    {
        if (null === $this->preferSlave) {
            return self::DEFAULT_IP_ADDRESS;
        }

        if (is_string($this->preferSlave)) {
            return $this->preferSlave;
        }

        $serverIpAddress = $this->ipAddressProvider->getServerIpAddress();
        if (!$serverIpAddress) {
            return self::DEFAULT_IP_ADDRESS;
        }

        return $this->preferSlave[$serverIpAddress] ?? self::DEFAULT_IP_ADDRESS;
    }

    /**
     * @param string|array|null $preferSlave The slave server IP address
     *                                       or [client application IP address => slave server IP address, ...]
     */
    public function setPreferSlave($preferSlave): void
    {
        if (!is_string($preferSlave) && !is_array($preferSlave)) {
            throw new \InvalidArgumentException('$preferSlave must be a string or an array.');
        }

        $this->preferSlave = $preferSlave;
    }

    public function setIpAddressProvider(IpAddressProvider $ipAddressProvider): void
    {
        $this->ipAddressProvider = $ipAddressProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function getHandlers(): array
    {
        $handlers = parent::getHandlers();
        $handlers['replication'] = ReplicationOption::class;

        return $handlers;
    }
}
