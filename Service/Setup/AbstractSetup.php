<?php

namespace Oro\Bundle\RedisConfigBundle\Service\Setup;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AbstractSetup
 * @package Oro\Bundle\RedisConfigBundle\Service\Setup
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
     * @param array $config
     * @return mixed
     */
    abstract public function getConfig(array $config);
}
