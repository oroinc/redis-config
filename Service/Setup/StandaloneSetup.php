<?php

namespace Oro\Bundle\RedisConfigBundle\Service\Setup;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class StandaloneSetup
 * @package Oro\Bundle\RedisConfigBundle\DependencyInjection
 */
class StandaloneSetup extends AbstractSetup
{
    /** setup type */
    const TYPE = 'standalone';
    
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
