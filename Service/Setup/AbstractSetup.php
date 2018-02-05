<?php

namespace Oro\Bundle\RedisConfigBundle\Service\Setup;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AbstractSetup
 * @package Oro\Bundle\RedisConfigBundle\DependencyInjection
 */
Abstract class AbstractSetup implements SetupInterface, ContainerAwareInterface
{
    /** @var ContainerInterface */
    protected $container;
    
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
    
    abstract public function getConfig(array $config);
}
