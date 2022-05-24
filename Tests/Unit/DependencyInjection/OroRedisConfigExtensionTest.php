<?php

namespace Oro\Bundle\RedisConfigBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\RedisConfigBundle\DependencyInjection\OroRedisConfigExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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
            ['redis_dsn_doctrine','redis://127.0.0.1:6379/1'],
            ['redis_dsn_layout','redis://127.0.0.1:6379/2']
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
                    'session_handler' => 'snc_redis.session.handler'
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
                    'session_handler' => 'snc_redis.session.handler'
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
        $this->assertEquals('cache.adapter.redis_tag_aware', $renderCache['adapter']);
        $this->assertEquals('snc_redis.layout', $renderCache['provider']);
    }
}
