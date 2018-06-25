<?php

namespace Oro\Bundle\RedisConfigBundle\Tests\Unit\Service;

use Oro\Bundle\RedisConfigBundle\Service\Setup;
use Oro\Bundle\RedisConfigBundle\Service\SetupFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

class SetupFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContainerBuilder
     */
    protected $container;

    protected function setUp()
    {
        $this->container = new ContainerBuilder();
    }

    /**
     * @dataProvider loadCacheParameterDataProvider
     * @param string $setup
     */
    public function testFactory($setup)
    {
        $factory = new SetupFactory();
        $factory->setContainer($this->container);
        $message = sprintf('You have requested a non-existent service "oro.redis_config.setup.%s"', $setup);
        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage($message);
        $factory->factory($setup);
    }

    /**
     * @return array
     */
    public function loadCacheParameterDataProvider()
    {
        return [
            [
                Setup\StandaloneSetup::TYPE,
            ],
            [
                Setup\ClusterSetup::TYPE,
            ],
            [
                Setup\SentinelSetup::TYPE,
            ],
        ];
    }
}
