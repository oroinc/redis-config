<?php

namespace Oro\Bundle\RedisConfigBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Yaml;

class OroRedisConfigExtension extends Extension implements PrependExtensionInterface
{
    /** @var  FileLocator */
    protected $fileLocator;

    /** {@inheritdoc} */
    public function __construct()
    {
        $this->fileLocator = new FileLocator(__DIR__ . '/../Resources/config');
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        if ($container->hasParameter('redis_dsn_cache') && $container->hasParameter('redis_dsn_session')) {
            $loader = new Loader\YamlFileLoader($container, $this->fileLocator);
            $loader->load('services.yml');
        }
    }

    /** {@inheritdoc} */
    public function prepend(ContainerBuilder $container)
    {
        if ($container->hasParameter('redis_dsn_cache') && $container->hasParameter('redis_dsn_session')) {
            $configs = Yaml::parse($this->fileLocator->locate('redis.yml'));
        } else {
            $configs = Yaml::parse($this->fileLocator->locate('redis_dummy.yml'));
        }
        foreach ($configs as $name => $config) {
            $container->prependExtensionConfig($name, $config);
        }
    }
}
