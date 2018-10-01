<?php

namespace Oro\Bundle\RedisConfigBundle\Tests\Behat\Isolation;

use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterFinishTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeStartTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\RestoreStateEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\IsolatorInterface;
use Predis\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RedisCacheIsolator implements IsolatorInterface
{
    private const REDIS_ENABLED_ENV_VAR = 'CACHE';

    /** @var array */
    private $knownClients;

    /** @var RedisCacheManipulator[] */
    private $manipulators = [];

    /**
     * @param array $knownClients
     */
    public function __construct(array $knownClients)
    {
        $this->knownClients = $knownClients;
    }

    /** {@inheritdoc} */
    public function isApplicable(ContainerInterface $container)
    {
        if (\getenv(self::REDIS_ENABLED_ENV_VAR) !== 'REDIS') {
            return false;
        }

        $this->buildManipulators($container);

        return (bool) $this->manipulators;
    }

    /** {@inheritdoc} */
    public function getName()
    {
        return 'Redis';
    }

    /** {@inheritdoc} */
    public function start(BeforeStartTestsEvent $event)
    {
        $event->writeln('<info>Save Redis state</info>');
        $event->writeln($this->buildMessage($this->saveRedisState(), __FUNCTION__));
    }

    /** {@inheritdoc} */
    public function beforeTest(BeforeIsolatedTestEvent $event)
    {
    }

    /** {@inheritdoc} */
    public function afterTest(AfterIsolatedTestEvent $event)
    {
        $event->writeln('<info>Restore Redis state</info>');
        $event->writeln($this->buildMessage($this->restoreRedisState(), __FUNCTION__));
    }

    /** {@inheritdoc} */
    public function terminate(AfterFinishTestsEvent $event)
    {
    }

    /** {@inheritdoc} */
    public function restoreState(RestoreStateEvent $event)
    {
        $event->writeln('<info>Restore Redis state</info>');
        $event->writeln($this->buildMessage($this->restoreRedisState(), __FUNCTION__));
    }

    /** {@inheritdoc} */
    public function isOutdatedState()
    {
        foreach ($this->manipulators as $manipulator) {
            $data = $manipulator->restoreData();
            if ($data) {
                return true;
            }
        }

        return false;
    }

    /** {@inheritdoc} */
    public function getTag()
    {
        return 'cache';
    }

    /**
     * @return array
     */
    private function saveRedisState(): array
    {
        $startTime = \microtime(true);

        $results = [];
        foreach ($this->manipulators as $manipulator) {
            $results[$manipulator->getName()] = $manipulator->saveRedisState();
        }

        return [\microtime(true) - $startTime, $results];
    }

    /**
     * @return array
     */
    private function restoreRedisState(): array
    {
        $startTime = \microtime(true);

        $results = [];
        foreach ($this->manipulators as $manipulator) {
            $results[$manipulator->getName()] = $manipulator->restoreRedisState();
        }

        return [\microtime(true) - $startTime, $results];
    }

    /**
     * @param ContainerInterface $container
     */
    private function buildManipulators(ContainerInterface $container): void
    {
        foreach ($this->knownClients as $serviceName => $type) {
            $manipulator = $this->buildManipulator($container, $serviceName, $type);
            if ($manipulator) {
                $this->manipulators[] = $manipulator;
            }
        }
    }

    /**
     * @param ContainerInterface $container
     * @param string $serviceName
     * @param string $name
     * @return null|RedisCacheManipulator
     */
    private function buildManipulator(
        ContainerInterface $container,
        string $serviceName,
        string $name
    ): ?RedisCacheManipulator {
        $service = $container->get($serviceName, ContainerInterface::NULL_ON_INVALID_REFERENCE);

        return $service instanceof Client ? new RedisCacheManipulator($service, $name) : null;
    }

    /**
     * @param array $data
     * @param string $method
     * @return string
     */
    private function buildMessage(array $data, string $method): string
    {
        return \sprintf(
            'Duration: %d ms. %s. Function: %s()',
            $data[0] * 1000,
            \implode(
                ' and ',
                \array_map(
                    function ($name, $count) {
                        return \sprintf('%d %s keys', $count, $name);
                    },
                    \array_keys($data[1]),
                    \array_values($data[1])
                )
            ),
            $method
        );
    }
}
