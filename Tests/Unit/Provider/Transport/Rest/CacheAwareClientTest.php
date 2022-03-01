<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Provider\Transport\Rest;

use Oro\Bundle\DotmailerBundle\Provider\Transport\Rest\CacheAwareClient;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Rest\DotmailerClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\ItemInterface;

class CacheAwareClientTest extends \PHPUnit\Framework\TestCase
{
    /** @var CacheAwareClient */
    protected $client;

    protected function setUp(): void
    {
        $this->client = new CacheAwareClient('namespace');
    }

    public function testFailIfClientNotInjected()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DotmailerClientInterface is not injected');

        $cache = $this->createMock(AbstractAdapter::class);
        $this->client->setCache($cache);

        $this->client->execute([]);
    }

    public function testSetBaseUrl()
    {
        $client = $this->createMock(DotmailerClientInterface::class);
        $client->expects($this->once())->method('setBaseUrl')->with('test');
        $this->client->setClient($client);
        $this->client->setBaseUrl('test');
    }

    public function testSetLogger()
    {
        $client = $this->createMock(DotmailerClientInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $client->expects($this->once())->method('setLogger')->with($logger);
        $this->client->setClient($client);
        $this->client->setLogger($logger);
    }

    public function testExecuteUnknownMethodActsLikeUnsafe()
    {
        $client = $this->createMock(DotmailerClientInterface::class);
        $cache = $this->createMock(AbstractAdapter::class);

        $this->client->setClient($client);
        $this->client->setCache($cache);

        $cache->expects($this->once())->method('clear');

        $client->expects($this->once())->method('execute');

        $this->client->execute([]);
    }

    public function testExecuteUnsafeMethod()
    {
        $client = $this->createMock(DotmailerClientInterface::class);
        $cache = $this->createMock(AbstractAdapter::class);

        $this->client->setClient($client);
        $this->client->setCache($cache);

        $cache->expects($this->once())->method('clear');

        $client->expects($this->once())->method('execute');

        $this->client->execute(['url', Request::METHOD_POST]);
    }

    public function testNonEmptyLogger()
    {
        $client = $this->createMock(DotmailerClientInterface::class);
        $cache = $this->createMock(AbstractAdapter::class);
        $logger = $this->createMock(LoggerInterface::class);

        $this->client->setClient($client);
        $this->client->setCache($cache);
        $this->client->setLogger($logger);
        $cache->expects($this->once())
            ->method('get')
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        $logger->expects($this->once())->method('debug');

        $this->client->execute(['url', Request::METHOD_GET]);
    }

    public function testExecuteSafeMethodNew()
    {
        $client = $this->createMock(DotmailerClientInterface::class);
        $cache = $this->createMock(AbstractAdapter::class);

        $this->client->setClient($client);
        $this->client->setCache($cache);

        $data = 'response data';
        $params = ['url', Request::METHOD_GET];

        $client->expects($this->once())->method('execute')->with($params)->willReturn($data);
        $cache->expects($this->once())
            ->method('get')
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        $this->client->execute($params);
    }

    public function testExecuteSafeMethodFromCache()
    {
        $client = $this->createMock(DotmailerClientInterface::class);
        $cache = $this->createMock(AbstractAdapter::class);

        $this->client->setClient($client);
        $this->client->setCache($cache);

        $data = 'response data';
        $params = ['url', Request::METHOD_GET];

        $cache->expects($this->once())->method('get')->willReturn($data);

        $this->client->execute($params);
    }
}
