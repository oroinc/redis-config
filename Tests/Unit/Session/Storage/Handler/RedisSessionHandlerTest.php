<?php

namespace Oro\Bundle\RedisConfigBundle\Tests\Unit\Session\Storage\Handler;

use \Predis\Client as RedisClient;
use Oro\Bundle\RedisConfigBundle\Session\Storage\Handler\RedisSessionHandler;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class RedisSessionHandlerTest extends \PHPUnit\Framework\TestCase
{
    private $maxExecutionTime = RedisSessionHandler::DEFAULT_MAX_EXECUTION_TIME;

    /** @var RedisSessionHandler*/
    private $handler;

    /** @var RedisClient|\PHPUnit\Framework\MockObject\MockObject */
    private $redis;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    protected function setUp(): void
    {
        $this->setMaxExecutionTime();
        $this->redis = $this->createMock(RedisClient::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->handler = new RedisSessionHandler(
            $this->redis,
            [],
            'session',
            true,
            1
        );
        $this->handler->setLogger($this->logger);
    }

    private function setMaxExecutionTime(): void
    {
        $this->maxExecutionTime = ini_get('max_execution_time');
        ini_set('max_execution_time', 0.000001);
    }

    protected function tearDown(): void
    {
        ini_set('max_execution_time', $this->maxExecutionTime);
        unset($_SERVER['REQUEST_URI']);
    }

    /**
     * @dataProvider logLockSessionDataProvider
     */
    public function testLogLockSession(string $logLevel): void
    {
        $_SERVER['REQUEST_URI'] = 'http://localhost/redis';
        $this->logger
            ->expects($this->once())
            ->method('log')
            ->with(
                $logLevel,
                $this->logicalAnd(
                    $this->stringContains('$lockMaxWait=1.0E-6'),
                    $this->stringContains('$ttl=0'),
                    $this->stringContains('$route=/redis'),
                )
            );
        $this->handler->setLogLevel($logLevel);
        $this->handler->read('session_id');
    }

    public function logLockSessionDataProvider(): \Generator
    {
        yield 'Notice level' => ['logLevel' => LogLevel::NOTICE];
        yield 'Critical level' => ['logLevel' => LogLevel::CRITICAL];
    }
}
