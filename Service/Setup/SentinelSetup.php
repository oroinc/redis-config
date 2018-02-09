<?php

namespace Oro\Bundle\RedisConfigBundle\Service\Setup;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SentinelSetup
 * @package Oro\Bundle\RedisConfigBundle\DependencyInjection
 */
class SentinelSetup extends AbstractSetup
{
    /** setup type */
    const TYPE = 'sentinel';

    const PARAMETER_REDIS_SENTINEL_MASTER_NAME = 'redis_sentinel_master_name';

    /**
     * @param array $config
     *
     * @return array
     */
    public function getConfig(array $config)
    {
        $this->container->setParameter('redis_setup', self::TYPE);
        $this->validate($config);
        $redisSentinelMasterName  = $this->container->getParameter('redis_sentinel_master_name');

        foreach ($config as $k => $v) {
            $dsnParameterValues = $this->container->getParameter(sprintf('redis_dsn_%s', $k));
            $dsns = [];
            $password = '';
            foreach ($dsnParameterValues as $dsnParameterValue) {
                $dsnParameterValueParts = explode('/', $dsnParameterValue);
                $database = array_pop($dsnParameterValueParts);
                $dsn = implode('/', $dsnParameterValueParts);
                if (strpos($dsn, '@')) {
                    $dsns[] = preg_replace('/(redis:\/\/)(.*\@)(.*)/', '$1$3', $dsn);
                    $password = preg_replace('/(redis:\/\/)(.*)(\@.*)/', '$2', $dsn);
                } else {
                    $dsns[] = $dsn;
                }
            }

            $config[$k]['dsn'] = $dsns;
            $config[$k]['options']['replication'] = self::TYPE;
            $config[$k]['options']['service'] = $redisSentinelMasterName;
            $config[$k]['options']['parameters']['database'] = $database;
            if ($password) {
                $config[$k]['options']['parameters']['password'] = $password;
            }
        }

        return $config;
    }

    /**
     * @param array $config
     * @throws \InvalidArgumentException
     */
    protected function validate($config)
    {
        $redisSentinelMasterName = $this->container->getParameter(self::PARAMETER_REDIS_SENTINEL_MASTER_NAME);
        if((null == $redisSentinelMasterName) || empty($redisSentinelMasterName) ){
            throw new \InvalidArgumentException(
                sprintf(
                    'Parameter %s has been missed',
                    self::PARAMETER_REDIS_SENTINEL_MASTER_NAME
                )
            );
        }
        foreach (array_keys($config) as $configKey) {
            $dsnParameters = $this->container->getParameter(sprintf('redis_dsn_%s', $configKey));
            if (!is_array($dsnParameters) || count($dsnParameters) < 2) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Parameter %s has invalid value. It must contain at least 2 sentinel server addresses',
                        'redis_dsn_' . $configKey
                    )
                );
            }
        }
    }
}
