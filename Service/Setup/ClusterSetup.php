<?php

namespace Oro\Bundle\RedisConfigBundle\Service\Setup;

/**
 * Class ClusterSetup
 * @package Oro\Bundle\RedisConfigBundle\Service\Setup
 */
class ClusterSetup extends AbstractSetup
{
    /** setup type */
    const TYPE = 'cluster';
    
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
