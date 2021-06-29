<?php

declare(strict_types=1);

namespace Oro\Bundle\RedisConfigBundle\Tests\Unit\Provider;

use Oro\Bundle\RedisConfigBundle\Provider\RedisRequirementsProvider;
use PHPUnit\Framework\TestCase;
use Predis\Client;

class RedisRequirementsProviderTest extends TestCase
{
    public function testNoAvailableClients()
    {
        $provider = $this->getProvider([]);
        $requirements = $provider->getOroRequirements();

        $this->assertNotNull($requirements);
        $this->assertCount(0, $requirements->all());
    }

    public function testConnectionNotConfigured()
    {
        $provider = $this->getProvider([
            'id' => $this->getClientMock('1.0', false)
        ]);
        $requirements = $provider->getOroRequirements()->all();

        $this->assertFalse($requirements[0]->isFulfilled());
        $this->assertEquals(
            'Connection for "id" service has invalid configuration.',
            $requirements[0]->getTestMessage()
        );
    }

    public function testVersionRequirementIsFulfilled()
    {
        $version = RedisRequirementsProvider::REQUIRED_VERSION;
        $provider = $this->getProvider([
            'id' => $this->getClientMock($version)
        ]);
        $requirements = $provider->getOroRequirements()->all();

        $this->assertTrue($requirements[0]->isFulfilled());
        $this->assertEquals(
            'Connection for "id" service has required Redis version (' . $version . ')',
            $requirements[0]->getTestMessage()
        );
    }

    public function testVersionRequirementNotFulfilled()
    {
        $provider = $this->getProvider([
            'id' => $this->getClientMock('1.0')
        ]);
        $requirements = $provider->getOroRequirements()->all();

        $this->assertFalse($requirements[0]->isFulfilled());
        $this->assertEquals(
            'Redis version of connection for "id" service must be ' .
                RedisRequirementsProvider::REQUIRED_VERSION . ' or higher',
            $requirements[0]->getHelpHtml()
        );
    }

    public function testMultipleClients()
    {
        $provider = $this->getProvider([
            'id1' => $this->getClientMock('1.0'),
            'id2' => $this->getClientMock('2.0'),
        ]);
        $requirements = $provider->getOroRequirements()->all();

        $this->assertCount(2, $requirements);
    }

    protected function getClientMock(string $version, bool $isConnected = true): Client
    {
        $client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $client->method('__call')
            ->withAnyParameters()
            ->willReturn(['Server' => ['redis_version' => $version]]);
        $client->method('isConnected')->willReturn($isConnected);

        return $client;
    }

    /**
     * @param Client[] $clients
     * @return RedisRequirementsProvider
     */
    protected function getProvider(array $clients): RedisRequirementsProvider
    {
        return new RedisRequirementsProvider($clients);
    }
}
