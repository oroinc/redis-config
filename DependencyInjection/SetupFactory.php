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
    /**
     * @param ContainerInterface $container
     *
     * @return ClusterSetup|SentinelSetup|StandaloneSetup
     */
    public static function factory(ContainerInterface $container)
    {
        $setup = $container->getParameter('redis_setup');
        
        switch ($setup){
            case SentinelSetup::TYPE:
                return new SentinelSetup($container);
                break;
            case ClusterSetup::TYPE:
                return new ClusterSetup($container);
                break;
            case StandaloneSetup::TYPE:
                return new StandaloneSetup($container);
                break;
            default:
                $availableSetups = [SentinelSetup::TYPE, ClusterSetup::TYPE, StandaloneSetup::TYPE];
                $validSetups = implode(', ', $availableSetups);
                throw new \InvalidArgumentException(
                    sprintf('Unknown setup : %s. Valid setups: %s', $setup,$validSetups)
                );
                break;
        }
    }
}
