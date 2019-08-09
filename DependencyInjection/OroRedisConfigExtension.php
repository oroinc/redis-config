<?php

namespace Oro\Bundle\RedisConfigBundle\DependencyInjection;

use Oro\Bundle\RedisConfigBundle\Service\Setup\StandaloneSetup;
use Oro\Bundle\RedisConfigBundle\Service\SetupFactory;
use Snc\RedisBundle\DependencyInjection\Configuration\RedisDsn;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Yaml;

/**
 * Class OroRedisConfigExtension checks that redis is enabled and load service configurations
 *
 * @package Oro\Bundle\RedisConfigBundle\DependencyInjection
 */
class OroRedisConfigExtension extends Extension implements PrependExtensionInterface
{
    use RedisEnabledCheckTrait;

    const REDIS_SESSION_HANDLER = 'snc_redis.session.handler';

    /** @var  FileLocator */
    protected $fileLocator;

    /** {@inheritdoc} */
    public function __construct()
    {
        $this->fileLocator = new FileLocator(__DIR__ . '/../Resources/config');
    }

    /**
     * @param array            $configs
     * @param ContainerBuilder $container
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, $this->fileLocator);
        if ($this->isRedisEnabledForCache($container)) {
            $loader->load('cache/services.yml');
        }
        if ($this->isRedisEnabledForDoctrine($container)) {
            $loader->load('doctrine/services.yml');
        }
    }

    /**
     * @param ContainerBuilder $container
     *
     * @throws \Exception
     */
    public function prepend(ContainerBuilder $container)
    {
        $configs = [[]];
        $isRedisEnabled = $this->isRedisEnabled($container);
        if ($isRedisEnabled) {
            $loader = new Loader\YamlFileLoader($container, $this->fileLocator);
            $loader->load('services.yml');

            $configs[] = $this->parseYmlConfig($this->fileLocator->locate('redis_enabled.yml'));

            $this->setContainerParameters($container);

            if ($this->isRedisEnabledForSessions($container)) {
                $configs[] = $this->loadAndValidateRedisClientConfig($container, 'session');
            }

            if ($this->isRedisEnabledForCache($container)) {
                $configs[] = $this->loadAndValidateRedisClientConfig($container, 'cache');
            }

            if ($this->isRedisEnabledForDoctrine($container)) {
                $configs[] = $this->loadAndValidateRedisClientConfig($container, 'doctrine');
            }
        } else {
            $configs[] = $this->parseYmlConfig($this->fileLocator->locate('redis_disabled.yml'));
        }
        foreach (\array_merge_recursive(...$configs) as $name => $config) {
            if ($isRedisEnabled && 'snc_redis' === $name) {
                /** @var SetupFactory $setupFactory */
                $setupFactory = $container->get('oro.redis_config.setup_factory');
                foreach (array_keys($config['clients']) as $client) {
                    $param = sprintf('redis_dsn_%s_type', $client);
                    $setupType = $container->hasParameter($param)
                        ? $container->getParameter($param)
                        : StandaloneSetup::TYPE;

                    $config['clients'][$client] = $setupFactory->factory($setupType)
                        ->setRedisClient($client)
                        ->getConfig($config['clients']);
                }
            }

            $container->prependExtensionConfig($name, $config);
        }
    }

    /**
     * @param string $filePath
     *
     * @return mixed
     */
    public function parseYmlConfig($filePath)
    {
        return Yaml::parse(file_get_contents($filePath));
    }


    /**
     * Need to disable connection persistence in case redis socket type DSN connection.
     *
     * @see https://github.com/snc/SncRedisBundle/pull/462/files
     * leads to
     * InvalidArgumentException: Persistent connection IDs are not supported when using UNIX domain sockets
     * when SOCKET used with persistent connection enabled
     * @see https://github.com/nrk/predis/blob/v1.1.1/src/Connection/StreamConnection.php#L199
     *
     * @param ContainerBuilder $container
     * @param string           $redisClient
     *
     * @return array
     */
    private function loadAndValidateRedisClientConfig(ContainerBuilder $container, string $redisClient): array
    {
        $config = $this->parseYmlConfig($this->fileLocator->locate($redisClient . '/config.yml'));
        $redisDsn = new RedisDsn($container->getParameter('redis_dsn_' . $redisClient));
        if ($redisDsn->getSocket()) {
            $config['snc_redis']['clients'][$redisClient]['options']['connection_persistent'] = false;
        }

        return $config;
    }

    /**
     * set container parameters for redis
     *
     * @param ContainerBuilder $container
     */
    protected function setContainerParameters(ContainerBuilder $container)
    {
        if (!$container->hasParameter('redis_session_type')
            || null === $container->getParameter('redis_session_type')
        ) {
            $container->setParameter('redis_session_type', StandaloneSetup::TYPE);
        }
        if (!$container->hasParameter('redis_cache_type')
            || null == $container->getParameter('redis_cache_type')
        ) {
            $container->setParameter('redis_cache_type', StandaloneSetup::TYPE);
        }
        if (!$container->hasParameter('redis_doctrine_type')
            || null == $container->getParameter('redis_doctrine_type')
        ) {
            $container->setParameter('redis_doctrine_type', StandaloneSetup::TYPE);
        }
    }
}
