<?php

namespace Oro\Bundle\RedisConfigBundle\DependencyInjection\Setup;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ClusterSetup
 * @package Oro\Bundle\RedisConfigBundle\DependencyInjection
 */
class ClusterSetup implements SetupInterface
{
    /** setup type */
    const TYPE = 'cluster';
    
    /** @var ContainerInterface */
    private $container;
    
    /**
     * ClusterSetup constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    /**
     * @param array $config
     *
     * @return array
     */
    public function getConfig(array $config)
    {
        $this->container->setParameter('redis_setup', self::TYPE);
        
        foreach ($config as $k => $v) {
            $dsnParameterValue = $this->container->getParameter(sprintf('redis_dsn_%s', $k));
            $config[$k]['dsn'] = (array)$dsnParameterValue;
            
            $config[$k]['options']['replication'] = true;
        }
        
        return $config;
    }
}
