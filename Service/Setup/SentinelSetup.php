<?php

namespace Oro\Bundle\RedisConfigBundle\Service\Setup;

/**
 * Configuration for sentinel setup
 */
class SentinelSetup extends AbstractSetup
{
    public const TYPE = 'sentinel';
    private const REDIS_SENTINEL_MASTER_NAME_PARAM = 'redis_%s_sentinel_master_name';

    public function getConfig(array $config, string $redisType): array
    {
        $this->validate($redisType);

        $redisSentinelMasterName = $this->container->getParameter(
            sprintf(self::REDIS_SENTINEL_MASTER_NAME_PARAM, $redisType)
        );
        $dsnParameterValues = $this->container->getParameter(sprintf('redis_dsn_%s', $redisType));
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

        $config[$redisType]['dsn'] = $dsns;
        $config[$redisType]['options']['replication'] = self::TYPE;
        $config[$redisType]['options']['service'] = $redisSentinelMasterName;
        $config[$redisType]['options']['parameters']['database'] = $database;
        if ($password) {
            $config[$redisType]['options']['parameters']['password'] = $password;
        }

        return $config[$redisType];
    }

    /**
     * @param string $redisType
     *
     * @throws \InvalidArgumentException
     */
    protected function validate($redisType): void
    {
        $redisSentinelMasterNameParam = sprintf(self::REDIS_SENTINEL_MASTER_NAME_PARAM, $redisType);
        $redisSentinelMasterName = $this->container->getParameter($redisSentinelMasterNameParam);
        if (empty($redisSentinelMasterName)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Parameter %s has been missed',
                    $redisSentinelMasterNameParam
                )
            );
        }
        $dsnParameters = $this->container->getParameter(sprintf('redis_dsn_%s', $redisType));
        if (!is_array($dsnParameters) || count($dsnParameters) < 2) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Parameter %s has invalid value. It must contain at least 2 sentinel server addresses',
                    'redis_dsn_' . $redisType
                )
            );
        }
    }
}
