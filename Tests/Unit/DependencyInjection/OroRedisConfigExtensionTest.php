<?php

namespace Oro\Bundle\RedisConfigBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\RedisConfigBundle\DependencyInjection\OroRedisConfigExtension;
use Oro\Bundle\RedisConfigBundle\Doctrine\Common\Cache\PredisCache;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class OroRedisConfigExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var OroRedisConfigExtension */
    private $extension;

    /** @var ContainerBuilder */
    private $container;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->extension = new OroRedisConfigExtension();
    }

    /**
     * @dataProvider loadCacheParameterDataProvider
     */
    public function testRedisEnabledForConfig(string $parameter, string $value)
    {
        $this->container->setParameter($parameter, $value);
        $this->assertTrue($this->extension->isRedisEnabled($this->container));
    }

    public function loadCacheParameterDataProvider(): array
    {
        return [
            ['redis_dsn_cache','redis://127.0.0.1:6379/0'],
            ['redis_dsn_doctrine','redis://127.0.0.1:6379/1']
        ];
    }

    /**
     * @dataProvider loadSessionParameterDataProvider
     */
    public function testRedisEnabledForSession(array $params, bool $isEnabled)
    {
        foreach ($params as $param => $value) {
            $this->container->setParameter($param, $value);
        }
        $this->assertEquals($isEnabled, $this->extension->isRedisEnabled($this->container));
    }

    public function loadSessionParameterDataProvider(): array
    {
        return [
            [
                [
                    'redis_dsn_session' => 'redis://127.0.0.1:6379/0',
                    'session_handler' => OroRedisConfigExtension::REDIS_SESSION_HANDLER
                ],
                true
            ],
            [
                [
                    'redis_dsn_session' => 'redis://127.0.0.1:6379/0',
                    'session_handler' => 'session.handler.native_file'
                ],
                false
            ],
            [
                [
                    'session_handler' => OroRedisConfigExtension::REDIS_SESSION_HANDLER
                ],
                false
            ],
        ];
    }

    public function testLoad()
    {
        $params = $this->loadCacheParameterDataProvider();
        foreach ($params as $values) {
            $this->container->setParameter($values[0], $values[1]);
        }
        $this->extension->load([], $this->container);
        $this->assertNotEmpty($this->container->getResources());
    }

    public function testCacheServicesDefinition()
    {
        $this->testLoad();
        $definition = $this->container->getDefinition('oro.cache.abstract');
        $this->assertEquals(PredisCache::class, $definition->getClass());
        $this->assertTrue($definition->isAbstract());
        $this->assertEquals(
            new Reference('snc_redis.cache'),
            $definition->getArgument(0)
        );
    }

    public function testDoctrineServicesDefinition()
    {
        $this->testLoad();
        $definition = $this->container->getDefinition('oro.doctrine.abstract');
        $this->assertEquals(PredisCache::class, $definition->getClass());
        $this->assertTrue($definition->isAbstract());
        $this->assertEquals(
            new Reference('snc_redis.doctrine'),
            $definition->getArgument(0)
        );
    }

    public function testPrependConfigRedisDisabled()
    {
        $this->extension->prepend($this->container);
        $config = $this->container->getExtensionConfig('snc_redis');
        $this->assertEmpty($config[0]['clients']['default']['dsns']);
    }

    public function testPrependConfigRedisEnabled()
    {
        $params = $this->loadCacheParameterDataProvider();
        foreach ($params as $values) {
            $this->container->setParameter($values[0], $values[1]);
        }
        $this->extension->prepend($this->container);
        $config = $this->container->getExtensionConfig('snc_redis');
        $this->assertArrayHasKey('cache', $config[0]['clients']);
        $this->assertArrayHasKey('doctrine', $config[0]['clients']);
        $this->assertNotEmpty($config[0]['clients']['cache']['dsn']);
        $this->assertNotEmpty($config[0]['clients']['doctrine']['dsn']);
    }
}
