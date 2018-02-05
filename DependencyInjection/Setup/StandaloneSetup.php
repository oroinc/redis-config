<?php

namespace Oro\Bundle\RedisConfigBundle\DependencyInjection\Setup;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class StandaloneSetup
 * @package Oro\Bundle\RedisConfigBundle\DependencyInjection
 */
class StandaloneSetup implements SetupInterface
{
    /** setup type */
    const TYPE = 'standalone';
    
    /** @var ContainerInterface  */
    private $container;
    
    /**
     * StandaloneSetup constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    /**
     * @param null $config
     *
     * @return null
     */
    public function getConfig(array $config)
    {
        $this->container->setParameter('redis_setup', self::TYPE);
        
        foreach ($config as $k => $v) {
            $dsnParameterValue = $this->container->getParameter(sprintf('redis_dsn_%s', $k));
            $config[$k]['dsn'] = (string)$dsnParameterValue;
        }
    
        return $config;
    }
}
