<?php

namespace Oro\Bundle\RedisConfigBundle\Tests\Unit\Service;

use Oro\Bundle\RedisConfigBundle\Service\Setup\ClusterSetup;
use Oro\Bundle\RedisConfigBundle\Service\Setup\SentinelSetup;
use Oro\Bundle\RedisConfigBundle\Service\Setup\StandaloneSetup;
use Oro\Bundle\RedisConfigBundle\Service\SetupFactory;

class SetupFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var SetupFactory */
    private $factory;

    protected function setUp()
    {
        $sentinelSetup = $this->createMock(SentinelSetup::class);
        $clusterSetup = $this->createMock(ClusterSetup::class);
        $standaloneSetup = $this->createMock(StandaloneSetup::class);

        $this->factory = new SetupFactory($sentinelSetup, $clusterSetup, $standaloneSetup);
    }

    /**
     * @dataProvider loadCacheParameterDataProvider
     * @param string $setupType
     * @param $expected
     */
    public function testFactory($setupType, $expected)
    {
        $actualSetup = $this->factory->factory($setupType);

        $this->assertInstanceOf($expected, $actualSetup);
    }

    public function testFactoryException()
    {
        $setupType = 'test_type';
        $availableSetups = [SentinelSetup::TYPE, ClusterSetup::TYPE, StandaloneSetup::TYPE];
        $validSetups = implode(', ', $availableSetups);

        $message = sprintf('Unknown setup : %s. Valid setups: %s', $setupType, $validSetups);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        $this->factory->factory($setupType);
    }

    /**
     * @return array
     */
    public function loadCacheParameterDataProvider()
    {
        return [
            [
                SentinelSetup::TYPE,
                SentinelSetup::class,
            ],
            [
                ClusterSetup::TYPE,
                ClusterSetup::class,
            ],
            [
                StandaloneSetup::TYPE,
                StandaloneSetup::class,
            ],
        ];
    }
}
