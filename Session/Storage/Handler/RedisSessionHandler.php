<?php

namespace Oro\Bundle\RedisConfigBundle\Session\Storage\Handler;

/**
 * Copyright (c) Henrik Westphal <henrik.westphal@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * This file is a copy of {@see \Snc\RedisBundle\Session\Storage\Handler\RedisSessionHandler}
 */

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use Snc\RedisBundle\Session\Storage\Handler\FreeLockCommand;
use Snc\RedisBundle\Session\Storage\Handler\RedisSessionHandler as BaseRedisSessionHandler;

/**
 * Redis based session storage with session locking support.
 */
class RedisSessionHandler extends BaseRedisSessionHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private string $logLevel = LogLevel::INFO;
    private string $lockKey;
    private ?string $token = null;
    private int $spinLockWait;

    public function __construct(
        $redis,
        array $options = [],
        string $prefix = 'session',
        bool $locking = true,
        int $spinLockWait = 150000
    ) {
        parent::__construct($redis, $options, $prefix, $locking, $spinLockWait);
        $this->locking = $locking;
        $this->locked = false;
        $this->spinLockWait = $spinLockWait;
    }

    public function setLogLevel(string $logLevel): void
    {
        $this->logLevel = $logLevel;
    }

    /**
     * {@inheritdoc}
     */
    public function close(): bool
    {
        if ($this->locking && $this->locked) {
            $this->unlockSession();
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function doRead($sessionId)
    {
        if ($this->locking && !$this->locked && !$this->lockSession($sessionId)) {
            return false;
        }

        return $this->redis->get($this->getRedisKey($sessionId)) ?: '';
    }

    /**
     * {@inheritdoc}
     */
    protected function doDestroy($sessionId): bool
    {
        $this->redis->del($this->getRedisKey($sessionId));
        $this->close();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function lockSession($sessionId): bool
    {
        $attempts = (1000000 / $this->spinLockWait) * $this->lockMaxWait;
        $this->token = uniqid();
        $this->lockKey = $this->getRedisKey($sessionId) . '.lock';

        $setFunction = function ($redis, $key, $token, $ttl) {
            if ($redis instanceof \Redis) {
                return $redis->set($key, $token, ['NX', 'PX' => $ttl]);
            }

            return $redis->set($key, $token, 'PX', $ttl, 'NX');
        };

        for ($i = 0; $i < $attempts; ++$i) {
            $success = $setFunction($this->redis, $this->lockKey, $this->token, $this->lockMaxWait * 1000 + 1);
            if ($success) {
                $this->locked = true;

                return true;
            }

            $this->logFailedSessionLock($sessionId);
            usleep($this->spinLockWait);
        }

        return false;
    }

    private function unlockSession(): void
    {
        if ($this->redis instanceof \Redis) {
            $script = <<<LUA
            if redis.call("GET", KEYS[1]) == ARGV[1] then
                return redis.call("DEL", KEYS[1])
            else
                return 0
            end
LUA;

            $token = $this->redis->_serialize($this->token);
            $this->redis->eval($script, [$this->lockKey, $token], 1);
        } else {
            $this->redis->getProfile()->defineCommand('sncFreeSessionLock', FreeLockCommand::class);
            $this->redis->sncFreeSessionLock($this->lockKey, $this->token);
        }
        $this->locked = false;
        $this->token = null;
    }

    private function logFailedSessionLock($sessionId): void
    {
        $message = sprintf(
            '[REDIS %s] Lock %s $lockMaxWait=%s $ttl=%s',
            $sessionId,
            json_encode(array_reverse($this->getStackTrace())),
            $this->lockMaxWait,
            $this->ttl
        );

        if (array_key_exists('REQUEST_URI', $_SERVER)) {
            $message = sprintf('%s $route=%s', $message, parse_url($_SERVER['REQUEST_URI'])['path']);
        }

        $this->logger->log($this->logLevel, $message);
    }

    private function getStackTrace(): array
    {
        $trace = [];
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $line) {
            if (empty($line['class'])) {
                $trace[$line['function']] = [];

                continue;
            }

            $class = explode('\\', $line['class']);
            $func = explode('\\', $line['function']);

            $trace[end($class)][] = $line['type'] . end($func);
        }
        array_pop($trace);

        return $trace;
    }

    /**
     * {@inheritdoc}
     */
    public function shutdown(): void
    {
        $this->close();
    }

    /**
     * {@inheritdoc}
     */
    public function __destruct()
    {
        $this->shutdown();
    }
}
