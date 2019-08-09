<?php

namespace Oro\Bundle\RedisConfigBundle\Service;

use Oro\Bundle\RedisConfigBundle\Service\Setup\ClusterSetup;
use Oro\Bundle\RedisConfigBundle\Service\Setup\SentinelSetup;
use Oro\Bundle\RedisConfigBundle\Service\Setup\StandaloneSetup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Factory for create different redis setups, depends on redis type(cache,doctrine,session)
 */
class SetupFactory
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param $redisType
     *
     * @return ClusterSetup|SentinelSetup|StandaloneSetup
     */
    public function factory($redisType)
    {
        $param = sprintf('redis_dsn_%s_type', $redisType);
        $setupType = $this->container->hasParameter($param) ?
            $this->container->getParameter($param) :
            StandaloneSetup::TYPE;

        switch ($setupType) {
            case SentinelSetup::TYPE:
                return $this->container->get('oro.redis_config.setup.sentinel');
                break;
            case ClusterSetup::TYPE:
                return $this->container->get('oro.redis_config.setup.cluster');
                break;
            case StandaloneSetup::TYPE:
                return $this->container->get('oro.redis_config.setup.standalone');
                break;
            default:
                $availableSetups = [SentinelSetup::TYPE, ClusterSetup::TYPE, StandaloneSetup::TYPE];
                $validSetups = implode(', ', $availableSetups);
                throw new \InvalidArgumentException(
                    sprintf('Unknown setup : %s. Valid setups: %s', $setupType, $validSetups)
                );
                break;
        }
    }
}
