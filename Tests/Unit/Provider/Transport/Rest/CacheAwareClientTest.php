<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Provider\Transport\Rest;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Rest\CacheAwareClient;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Rest\DotmailerClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

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

        $cache = $this->createMock(CacheProvider::class);
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
        $cache = $this->createMock(CacheProvider::class);

        $this->client->setClient($client);
        $this->client->setCache($cache);

        $cache->expects($this->never())->method('save');
        $cache->expects($this->once())->method('deleteAll');

        $client->expects($this->once())->method('execute');

        $this->client->execute([]);
    }

    public function testExecuteUnsafeMethod()
    {
        $client = $this->createMock(DotmailerClientInterface::class);
        $cache = $this->createMock(CacheProvider::class);

        $this->client->setClient($client);
        $this->client->setCache($cache);

        $cache->expects($this->never())->method('save');
        $cache->expects($this->once())->method('deleteAll');

        $client->expects($this->once())->method('execute');

        $this->client->execute(['url', Request::METHOD_POST]);
    }

    public function testNonEmptyLogger()
    {
        $client = $this->createMock(DotmailerClientInterface::class);
        $cache = $this->createMock(CacheProvider::class);
        $logger = $this->createMock(LoggerInterface::class);

        $this->client->setClient($client);
        $this->client->setCache($cache);
        $this->client->setLogger($logger);

        $logger->expects($this->once())->method('debug');

        $this->client->execute(['url', Request::METHOD_GET]);
    }

    public function testExecuteSafeMethodNew()
    {
        $client = $this->createMock(DotmailerClientInterface::class);
        $cache = $this->createMock(CacheProvider::class);

        $this->client->setClient($client);
        $this->client->setCache($cache);

        $data = ['array with data'];
        $params = ['url', Request::METHOD_GET];

        $cache->expects($this->once())->method('fetch')->willReturn(false);
        $client->expects($this->once())->method('execute')->with($params)->willReturn($data);
        $cache->expects($this->once())->method('save')
            ->with(
                $this->isType('string'),
                $data,
                $this->isType('integer')
            );

        $this->client->execute($params);
    }

    public function testExecuteSafeMethodFromCache()
    {
        $client = $this->createMock(DotmailerClientInterface::class);
        $cache = $this->createMock(CacheProvider::class);

        $this->client->setClient($client);
        $this->client->setCache($cache);

        $data = ['array with data'];
        $params = ['url', Request::METHOD_GET];

        $cache->expects($this->once())->method('fetch')->willReturn($data);
        $client->expects($this->never())->method('execute');
        $cache->expects($this->never())->method('save');

        $this->client->execute($params);
    }
}
