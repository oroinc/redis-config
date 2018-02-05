<?php

namespace Oro\Bundle\RedisConfigBundle\DependencyInjection;

use Oro\Bundle\RedisConfigBundle\DependencyInjection\Setup\StandaloneSetup;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Yaml;
use Predis\Command\RawCommand;

/**
 * Class OroRedisConfigExtension
 * @package Oro\Bundle\RedisConfigBundle\DependencyInjection
 */
class OroRedisConfigExtension extends Extension implements PrependExtensionInterface
{
    const REDIS_SESSION_HANDLER = 'snc_redis.session.handler';
    
    /** @var  FileLocator */
    protected $fileLocator;

    /** {@inheritdoc} */
    public function __construct()
    {
        $this->fileLocator = new FileLocator(__DIR__ . '/../Resources/config');
    }

    /**
     * @param ContainerBuilder $container
     * @param string $paramName
     * @return bool
     */
    private function validateRedisConfigDsnValue(ContainerBuilder $container, $paramName)
    {
        if (!$container->hasParameter($paramName) || (null === $container->getParameter($paramName))) {
            return false;
        }
        
        return true;
    }

    /**
     * @param ContainerBuilder $container
     * @return bool
     */
    protected function isRedisEnabledForSessions(ContainerBuilder $container)
    {
        if ($this->validateRedisConfigDsnValue($container, 'redis_dsn_session')
        && self::REDIS_SESSION_HANDLER == $container->getParameter('session_handler')) {
            return true;
        }
        
        return false;
    }

    /**
     * @param ContainerBuilder $container
     * @return bool
     */
    protected function isRedisEnabledForCache(ContainerBuilder $container)
    {
        if ($this->validateRedisConfigDsnValue($container, 'redis_dsn_cache')) {
            return true;
        }

        return false;
    }

    /**
     * @param ContainerBuilder $container
     * @return bool
     */
    protected function isRedisEnabledForDoctrine(ContainerBuilder $container)
    {
        if ($this->validateRedisConfigDsnValue($container, 'redis_dsn_doctrine')) {
            return true;
        }

        return false;
    }

    /**
     * @param ContainerBuilder $container
     * @return bool
     */
    private function isRedisEnabled(ContainerBuilder $container)
    {
        return $this->isRedisEnabledForSessions($container)
            || $this->isRedisEnabledForCache($container)
            || $this->isRedisEnabledForDoctrine($container);
    }

    /**
     * {@inheritdoc}
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

    /** {@inheritdoc} */
    public function prepend(ContainerBuilder $container)
    {
        $isRedisEnabled = true;
        
        $configs = [[]];
        if ($this->isRedisEnabled($container)) {
            if(!$container->hasParameter('redis_setup') ||
               (null == $container->getParameter('redis_setup'))){
                $container->setParameter('redis_setup', StandaloneSetup::TYPE);
            }
            
            if ($this->isRedisEnabledForSessions($container)) {
                $configs[] = Yaml::parse($this->fileLocator->locate('session/config.yml'));
            }
            if ($this->isRedisEnabledForCache($container)) {
                $configs[] = Yaml::parse($this->fileLocator->locate('cache/config.yml'));
            }
            if ($this->isRedisEnabledForDoctrine($container)) {
                $configs[] = Yaml::parse($this->fileLocator->locate('doctrine/config.yml'));
            }
        } else {
            $isRedisEnabled = false;
            $configs[] = Yaml::parse($this->fileLocator->locate('redis_disabled.yml'));
        }

        foreach (\array_merge_recursive(...$configs) as $name => $config) {
            if($isRedisEnabled && ('snc_redis' === $name)){
                $config['clients'] = SetupFactory::factory($container)->getConfig($config['clients']);
            }
            $container->prependExtensionConfig($name, $config);
        }
    }
}
