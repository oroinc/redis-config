<?php

namespace Oro\Bundle\RedisConfigBundle\DependencyInjection;

use Oro\Bundle\RedisConfigBundle\Service\Setup\StandaloneSetup;
use Oro\Bundle\RedisConfigBundle\Service\SetupFactory;
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
     * @param array $configs
     * @param ContainerBuilder $container
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
     * @throws \Exception
     */
    public function prepend(ContainerBuilder $container)
    {
        $isRedisEnabled = true;
        $configs = [[]];
        if ($this->isRedisEnabled($container)) {
            $loader = new Loader\YamlFileLoader($container, $this->fileLocator);
            $loader->load('services.yml');
            $configs[] = $this->parseYmlConfig($this->fileLocator->locate('redis_enabled.yml'));

            if (!$container->hasParameter('redis_setup') ||
                (null == $container->getParameter('redis_setup'))) {
                $container->setParameter('redis_setup', StandaloneSetup::TYPE);
            }

            if ($this->isRedisEnabledForSessions($container)) {
                $configs[] = $this->parseYmlConfig($this->fileLocator->locate('session/config.yml'));
            }
            if ($this->isRedisEnabledForCache($container)) {
                $configs[] = $this->parseYmlConfig($this->fileLocator->locate('cache/config.yml'));
            }
            if ($this->isRedisEnabledForDoctrine($container)) {
                $configs[] = $this->parseYmlConfig($this->fileLocator->locate('doctrine/config.yml'));
            }
        } else {
            $isRedisEnabled = false;
            $configs[] = $this->parseYmlConfig($this->fileLocator->locate('redis_disabled.yml'));
        }

        foreach (\array_merge_recursive(...$configs) as $name => $config) {
            if ($isRedisEnabled && ('snc_redis' === $name)) {
                $setupType = $container->getParameter('redis_setup');
                /** @var SetupFactory $setupFactory */
                $setupFactory = $container->get('oro.redis_config.setup_factory');
                $config['clients'] = $setupFactory->factory($setupType)->getConfig($config['clients']);
            }
            $container->prependExtensionConfig($name, $config);
        }
    }

    /**
     * @param string $filePath
     * @return mixed
     */
    public function parseYmlConfig($filePath)
    {
        return Yaml::parse(file_get_contents($filePath));
    }
}
