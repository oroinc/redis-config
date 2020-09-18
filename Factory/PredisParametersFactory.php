<?php

/*
 * This file is a copy of {@see \Oro\Bundle\RedisConfigBundle\Factory\PredisParametersFactory}
 *
 * (c) Henrik Westphal <henrik.westphal@gmail.com>
 */

namespace Oro\Bundle\RedisConfigBundle\Factory;

use Predis\Connection\ParametersInterface;
use Snc\RedisBundle\DependencyInjection\Configuration\RedisDsn;

/**
 * The predis parameters factory that fixes persistent connections to the 0 and 1 redis database with
 * `tcp` and `redis` schema socket connections.
 *
 * The issue can happens when the system should have 2 separate persistent connections to 0 and 1 databases with
 * tcp connection.
 * Without the fix, the 'persistent' parameter will have the true as value for the 0 database and 1 for the 1 database.
 * In this case, the connection string for this connections will be the same and will use one general connection
 * in tcpStreamInitializer method of {@see \Predis\Connection\StreamConnection} class.
 * As the result data from one database can be written to another database.
 *
 * With the fix, the 'persistent' parameter is the string, so at this case, the connection strings will be different.
 */
class PredisParametersFactory
{
    /**
     * @param array $options
     * @param string $class
     * @param string $dsn
     *
     * @return ParametersInterface
     */
    public static function create($options, $class, $dsn)
    {
        if (!is_a($class, ParametersInterface::class, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    '%s::%s requires $class argument to implement %s',
                    __CLASS__,
                    __METHOD__,
                    ParametersInterface::class
                )
            );
        }

        // Allow to be consistent will old version of Predis where default timeout was 5
        $defaultOptions = ['timeout' => null];
        $dsnOptions = static::parseDsn(new RedisDsn($dsn));
        $dsnOptions = array_merge($defaultOptions, $options, $dsnOptions);

        if (isset($dsnOptions['persistent'], $dsnOptions['database']) && true === $dsnOptions['persistent']) {
            $dbId = (int)$dsnOptions['database'];
            if (in_array($dsnOptions['scheme'], ['tcp', 'redis'], true)) {
                // set the `persistent` parameter as the string to be sure the connection will be a separate connection
                $dsnOptions['persistent'] = 'db' . $dbId;
            } elseif ($dbId !== 0) {
                $dsnOptions['persistent'] = $dbId;
            }
        }

        return new $class($dsnOptions);
    }

    /**
     * @param RedisDsn $dsn
     *
     * @return array
     */
    private static function parseDsn(RedisDsn $dsn)
    {
        if (null !== $dsn->getSocket()) {
            $options['scheme'] = 'unix';
            $options['path'] = $dsn->getSocket();
        } else {
            $options['scheme'] = $dsn->getTls() ? 'tls' : 'tcp';
            $options['host'] = $dsn->getHost();
            $options['port'] = $dsn->getPort();
            if (null !== $dsn->getDatabase()) {
                $options['path'] = $dsn->getDatabase();
            }
        }

        if (null !== $dsn->getDatabase()) {
            $options['database'] = $dsn->getDatabase();
        }

        $options['password'] = $dsn->getPassword();
        $options['weight'] = $dsn->getWeight();

        if (null !== $dsn->getAlias()) {
            $options['alias'] = $dsn->getAlias();
        }

        return $options;
    }
}
