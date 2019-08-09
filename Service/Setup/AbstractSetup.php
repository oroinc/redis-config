<?php

namespace Oro\Bundle\RedisConfigBundle\Service\Setup;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * {@inheritdoc}
 */
abstract class AbstractSetup implements SetupInterface, ContainerAwareInterface
{
    /** @var ContainerInterface */
    protected $container;

    /** @var string */
    protected $redisClient;

    /**
     * @param ContainerInterface|null $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param string $redisClient Possible values 'session', 'cache', doctrine
     *
     * @return AbstractSetup
     */
    public function setRedisClient(string $redisClient)
    {
        $this->redisClient = $redisClient;

        return $this;
    }

    public function getRedisClient()
    {
        return $this->redisClient ? : 'default';
    }

    /**
     * @param array $config
     *
     * @return array
     */
    abstract public function getConfig(array $config);
}
