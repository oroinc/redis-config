<?php

namespace Oro\Bundle\RedisConfigBundle\Configuration;

class Options extends \Predis\Configuration\Options
{
    /** @var string  */
    protected $preferSlave;

    /**
     * Options constructor.
     * @param array $options
     * @param string $preferSlave
     */
    public function __construct(array $options = [], $preferSlave = '127.0.0.1')
    {
        parent::__construct($options);
        $this->preferSlave = $preferSlave;
    }

    /**
     * Rewrite "replication" handler
     *
     * @return array
     */
    protected function getHandlers()
    {
        $handlers = parent::getHandlers();
        $handlers['replication'] = 'Oro\Bundle\RedisConfigBundle\Configuration\ReplicationOption';
        return $handlers;
    }

    /**
     * @return string
     */
    public function getPreferSlave()
    {
        return $this->preferSlave;
    }
}