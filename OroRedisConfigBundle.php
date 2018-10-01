<?php

namespace Oro\Bundle\RedisConfigBundle;

use Oro\Bundle\RedisConfigBundle\DependencyInjection\Compiler\ConfigCompilerPass;
use Oro\Bundle\RedisConfigBundle\DependencyInjection\Compiler\DoctrineCacheCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The RedisConfigBundle bundle class.
 */
class OroRedisConfigBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ConfigCompilerPass());
        $container->addCompilerPass(new DoctrineCacheCompilerPass());
    }
}
