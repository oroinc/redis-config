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

    /**
     * @param ContainerInterface|null $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param array  $config
     * @param string $redisType
     *
     * @return array
     */
    abstract public function getConfig(array $config, string $redisType);
}
