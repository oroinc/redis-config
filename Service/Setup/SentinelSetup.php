<?php

namespace Oro\Bundle\RedisConfigBundle\Service\Setup;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Predis\Command\RawCommand;

/**
 * Class SentinelSetup
 * @package Oro\Bundle\RedisConfigBundle\DependencyInjection
 */
class SentinelSetup extends AbstractSetup
{
    /** setup type */
    const TYPE = 'sentinel';
    
    const PARAMETER_REDIS_SENTINEL_MASTER_NAME = 'redis_sentinel_master_name';
    
    /**
     * @param array $config
     *
     * @return array
     */
    public function getConfig(array $config)
    {
        $this->container->setParameter('redis_setup', self::TYPE);
        
        $this->validate();
        
        $redisSentinelPreferSlave = $this->container->getParameter('redis_sentinel_prefer_slave');
        $redisSentinelMasterName  = $this->container->getParameter('redis_sentinel_master_name');
        
        foreach ($config as $k => $v) {
            $dsnParameterValue      = $this->container->getParameter(sprintf('redis_dsn_%s', $k));
            $dsn                    = [];
            $dsnParameterValueParts = explode('/', $dsnParameterValue);
            $sentinelEndpoint       = sprintf('%s//%s', $dsnParameterValueParts[0], $dsnParameterValueParts[2]);
    
            $client                   = new \Predis\Client($sentinelEndpoint);
            $masterPayload            = $client->executeCommand(
                RawCommand::create(
                    'SENTINEL',
                    'get-master-addr-by-name',
                    $redisSentinelMasterName
                )
            );
            $dsn[] = 'redis://' . $masterPayload[0] . ':' . $masterPayload[1] . '/' . $dsnParameterValueParts[3] . '?alias=master';
            $slavesPayload = $client->executeCommand(
                RawCommand::create('SENTINEL', 'slaves', $redisSentinelMasterName)
            );
            
            $dsnSlaves = [];
            foreach ($slavesPayload as $cnt => $item) {
                $dsnSlave = 'redis://' . $item[1] . '/' . $dsnParameterValueParts[3];
                if ( ! empty($redisSentinelPreferSlave) && ($redisSentinelPreferSlave == $item[3])) {
                    array_unshift($dsnSlaves, $dsnSlave);
                } else {
                    array_push($dsnSlaves, $dsnSlave);
                }
            }
    
            $dsn  = array_merge($dsn, $dsnSlaves);
            
            $config[$k]['dsn'] = $dsn;
            $config[$k]['options']['replication'] = true;
        }
        
        return $config;
    }
    
    /**
     * validation
     */
    protected function validate()
    {
        if(!$this->container->hasParameter(self::PARAMETER_REDIS_SENTINEL_MASTER_NAME)){
            throw new \RuntimeException(
                sprintf('Missing parameter %s', self::PARAMETER_REDIS_SENTINEL_MASTER_NAME)
            );
        }
        $redisSentinelMasterName = $this->container->getParameter(self::PARAMETER_REDIS_SENTINEL_MASTER_NAME);
        if((null == $redisSentinelMasterName) || empty($redisSentinelMasterName) ){
            throw new \InvalidArgumentException(
                sprintf(
                    'Parameter %s has invalid  value. It should contain valid master-name like mymaster',
                    self::PARAMETER_REDIS_SENTINEL_MASTER_NAME,
                    $redisSentinelMasterName
                )
            );
        }
    }
}
