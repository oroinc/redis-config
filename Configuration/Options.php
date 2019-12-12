<?php

namespace Oro\Bundle\RedisConfigBundle\Configuration;

/**
 * Adds the preferSlave option that allows to configure a preferable slave server for an application.
 */
class Options extends \Predis\Configuration\Options
{
    /** @var string|array */
    protected $preferSlave;

    /** @var IpAddressProvider */
    private $ipAddressProvider;

    /**
     * @param array        $options
     * @param string|array $preferSlave The slave server IP address
     *                                  or [client application IP address => slave server IP address, ...]
     */
    public function __construct(array $options = [], $preferSlave = '127.0.0.1')
    {
        if (!is_string($preferSlave) && !is_array($preferSlave)) {
            throw new \InvalidArgumentException('$preferSlave must be a string or an array.');
        }

        parent::__construct($options);
        $this->preferSlave = $preferSlave;
    }

    /**
     * @return string
     */
    public function getPreferSlave(): string
    {
        if (is_string($this->preferSlave)) {
            return $this->preferSlave;
        }

        $serverIpAddress = $this->ipAddressProvider->getServerIpAddress();
        if (!$serverIpAddress) {
            return '127.0.0.1';
        }

        return $this->preferSlave[$serverIpAddress] ?? '127.0.0.1';
    }

    /**
     * @param IpAddressProvider $ipAddressProvider
     */
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
        $handlers['replication'] = 'Oro\Bundle\RedisConfigBundle\Configuration\ReplicationOption';

        return $handlers;
    }
}
