<?php

namespace Oro\Bundle\RedisConfigBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Oro\Bundle\RedisConfigBundle\DependencyInjection\Setup\SentinelSetup;
use Oro\Bundle\RedisConfigBundle\DependencyInjection\Setup\ClusterSetup;
use Oro\Bundle\RedisConfigBundle\DependencyInjection\Setup\StandaloneSetup;

/**
 * Class SetupFactory
 * @package Oro\Bundle\RedisConfigBundle\DependencyInjection
 */
final class SetupFactory
{
    const SETUP_SENTINEL   = 'sentinel';
    const SETUP_CLUSTER    = 'cluster';
    const SETUP_STANDALONE = 'standalone';
    
    /**
     * @param ContainerInterface $container
     *
     * @return ClusterSetup|SentinelSetup|StandaloneSetup
     */
    public static function factory(ContainerInterface $container)
    {
        $setup = $container->getParameter('redis_setup');
        
        switch ($setup){
            case self::SETUP_SENTINEL:
                return new SentinelSetup($container);
                break;
            case self::SETUP_CLUSTER:
                return new ClusterSetup($container);
                break;
            case self::SETUP_STANDALONE:
                return new StandaloneSetup($container);
                break;
            default:
                $availableSetups = [self::SETUP_SENTINEL,self::SETUP_CLUSTER, self::SETUP_STANDALONE];
                $validSetups = implode(', ', $availableSetups);
                $msg = 'Unknown redis setup: %s. Valid redis setups: %s';
                throw new \InvalidArgumentException(
                    sprintf('Unknown setup : %s. Valid setups: %s', $setup,$validSetups)
                );
                break;
        }
    }
}
