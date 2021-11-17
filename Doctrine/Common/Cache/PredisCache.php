<?php

namespace Oro\Bundle\RedisConfigBundle\Doctrine\Common\Cache;

use Predis\ClientInterface;
use Symfony\Component\Cache\Adapter\RedisAdapter;

/**
 * Overrides \Oro\Bundle\RedisConfigBundle\Doctrine\Common\Cache\PredisCache to fix
 * {@see https://github.com/doctrine/cache/pull/361}
 */
class PredisCache extends RedisAdapter
{
    private ClientInterface $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
        parent::__construct($client);
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetchMultiple(array $keys)
    {
        $fetchedItems = \call_user_func_array([$this->client, 'mget'], \array_values($keys));

        return \array_map('unserialize', \array_filter(array_combine($keys, $fetchedItems)));
    }
}
