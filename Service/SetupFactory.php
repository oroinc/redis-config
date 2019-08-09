<?php

namespace Oro\Bundle\RedisConfigBundle\Service;

use Oro\Bundle\RedisConfigBundle\Service\Setup\ClusterSetup;
use Oro\Bundle\RedisConfigBundle\Service\Setup\SentinelSetup;
use Oro\Bundle\RedisConfigBundle\Service\Setup\StandaloneSetup;

/**
 * Provide functionality to change setup type
 */
class SetupFactory
{
    /** @var SentinelSetup */
    private $sentinelSetup;

    /** @var ClusterSetup */
    private $clusterSetup;

    /** @var StandaloneSetup */
    private $standaloneSetup;

    /**
     * @param SentinelSetup   $sentinelSetup
     * @param ClusterSetup    $clusterSetup
     * @param StandaloneSetup $standaloneSetup
     */
    public function __construct(
        SentinelSetup $sentinelSetup,
        ClusterSetup $clusterSetup,
        StandaloneSetup $standaloneSetup
    ) {
        $this->sentinelSetup = $sentinelSetup;
        $this->clusterSetup = $clusterSetup;
        $this->standaloneSetup = $standaloneSetup;
    }

    /**
     * @param string $setupType
     *
     * @return ClusterSetup|SentinelSetup|StandaloneSetup
     */
    public function factory(string $setupType)
    {
        switch ($setupType) {
            case SentinelSetup::TYPE:
                return $this->sentinelSetup;
                break;
            case ClusterSetup::TYPE:
                return $this->clusterSetup;
                break;
            case StandaloneSetup::TYPE:
                return $this->standaloneSetup;
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
