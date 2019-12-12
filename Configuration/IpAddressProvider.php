<?php

namespace Oro\Bundle\RedisConfigBundle\Configuration;

/**
 * A service that can be used to get the IP address of the server.
 */
class IpAddressProvider
{
    /** @var string|null */
    private $serverIpAddress;

    /**
     * @param string|null $serverIpAddress
     */
    public function __construct(string $serverIpAddress = null)
    {
        if ('' === $serverIpAddress) {
            $serverIpAddress = null;
        }
        $this->serverIpAddress = $serverIpAddress;
    }

    /**
     * Returns the IP address of the server under which the current script is executing.
     *
     * @return string
     */
    public function getServerIpAddress(): string
    {
        if (null === $this->serverIpAddress) {
            $host = gethostname();
            $this->serverIpAddress = $host
                ? gethostbyname($host)
                : '';
        }

        return $this->serverIpAddress;
    }
}
