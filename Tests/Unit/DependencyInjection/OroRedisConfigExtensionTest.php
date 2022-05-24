<?php

namespace Oro\Bundle\RedisConfigBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\RedisConfigBundle\DependencyInjection\OroRedisConfigExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class OroRedisConfigExtensionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var OroRedisConfigExtension
     */
    protected $extension;

    /**
     * @var ContainerBuilder
     */
    protected $container;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->extension = new OroRedisConfigExtension();
    }

    /**
     * @dataProvider loadCacheParameterDataProvider
     *
     * @param string $parameter
     * @param string $value
     */
    public function testRedisEnabledForConfig($parameter, $value)
    {
        $this->container->setParameter($parameter, $value);
        $this->assertTrue($this->extension->isRedisEnabled($this->container));
    }

    /**
     * @return array
     */
    public function loadCacheParameterDataProvider()
    {
        return [
            ['redis_dsn_cache','redis://127.0.0.1:6379/0'],
            ['redis_dsn_doctrine','redis://127.0.0.1:6379/1'],
            ['redis_dsn_layout','redis://127.0.0.1:6379/2']
        ];
    }

    /**
     * @dataProvider loadSessionParameterDataProvider
     *
     * @param array $params
     * @param bool $isEnabled
     */
    public function testRedisEnabledForSession($params, $isEnabled)
    {
        foreach ($params as $param => $value) {
            $this->container->setParameter($param, $value);
        }
        $this->assertEquals($isEnabled, $this->extension->isRedisEnabled($this->container));
    }

    /**
     * @return array
     */
    public function loadSessionParameterDataProvider()
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
        $this->extension->load(array(), $this->container);
        $this->assertNotEmpty($this->container->getResources());
    }

    public function testCacheServicesDefinition()
    {
        $this->testLoad();
        $definition = $this->container->getDefinition('oro.cache.abstract');
        $this->assertEquals('Oro\Bundle\RedisConfigBundle\Doctrine\Common\Cache\PredisCache', $definition->getClass());
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
        $this->assertEquals('Oro\Bundle\RedisConfigBundle\Doctrine\Common\Cache\PredisCache', $definition->getClass());
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
        $this->assertArrayHasKey('layout', $config[0]['clients']);
        $this->assertNotEmpty($config[0]['clients']['cache']['dsn']);
        $this->assertNotEmpty($config[0]['clients']['doctrine']['dsn']);
        $this->assertNotEmpty($config[0]['clients']['layout']['dsn']);
    }

    public function testPrependConfigLayoutCacheEnabled(): void
    {
        $params = $this->loadCacheParameterDataProvider();
        foreach ($params as $values) {
            $this->container->setParameter($values[0], $values[1]);
        }
        $this->extension->prepend($this->container);
        $config = $this->container->getExtensionConfig('framework');
        $renderCache = $config[0]['cache']['pools']['cache.oro_layout.render'];
        $this->assertEquals('cache.adapter.redis', $renderCache['adapter']);
        $this->assertEquals('snc_redis.layout', $renderCache['provider']);
        $this->assertTrue($renderCache['tags']);
    }
}
