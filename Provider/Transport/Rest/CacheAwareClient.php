<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Transport\Rest;

use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\DotmailerBundle\Provider\Transport\CacheProviderAwareTrait;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Client service for Dotmailer request/response handling
 */
class CacheAwareClient implements DotmailerClientInterface
{
    use LoggerAwareTrait, CacheProviderAwareTrait;

    /**
     * Should be more than
     * @see \Oro\Bundle\MessageQueueBundle\DependencyInjection\Configuration::getConfigTreeBuilder
     * redelivered_delay_time 10 minutes
     * and
     * @see \Oro\Bundle\DotmailerBundle\Command\ContactsExportStatusUpdateCommand::getDefaultDefinition
     * 5 minutes cron definition
     */
    private const REDELIVERED_DELAY_TIME = 900;

    private ?DotmailerClientInterface $client = null;
    private string $namespace;

    public function __construct($username, $password = null)
    {
        $this->namespace = $username;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;

        $this->getClient()->setLogger($logger);
    }

    public function getLogger(): LoggerInterface
    {
        if (!$this->logger) {
            $this->logger = new NullLogger();
        }

        return $this->logger;
    }

    public function setBaseUrl(string $url): void
    {
        $this->getClient()->setBaseUrl($url);
    }

    public function execute($paramArr, $responses = []): ?string
    {
        list(, $requestMethod) = array_pad(array_values($paramArr), 2, null);

        if (in_array($requestMethod, [Request::METHOD_GET, Request::METHOD_HEAD, Request::METHOD_OPTIONS], true)) {
            return $this->processSafeRequest($paramArr, $responses);
        }

        return $this->processNonSafeRequest($paramArr, $responses);
    }

    private function processSafeRequest(array $paramArr, array $responses = []): mixed
    {
        $cacheKey = $this->getCacheKey($paramArr);
        return $this->getCache()->get($cacheKey, function (ItemInterface $item) use ($paramArr, $responses) {
            $item->expiresAfter(self::REDELIVERED_DELAY_TIME);
            $this->getLogger()->debug('[DM] Save data cache', $paramArr);
            return $this->getClient()->execute($paramArr, $responses);
        });
    }

    private function processNonSafeRequest(array $paramArr, array $responses = []): mixed
    {
        $this->getCache()->clear();
        $this->getLogger()->debug('[DM] Delete data from cache', $paramArr);

        return $this->getClient()->execute($paramArr, $responses);
    }

    private function getCacheKey(array $paramArr = []): string
    {
        list($requestUrl) = array_pad(array_values($paramArr), 1, null);
        return UniversalCacheKeyGenerator::normalizeCacheKey($this->namespace . md5($requestUrl));
    }

    public function setClient(DotmailerClientInterface $client): void
    {
        $this->client = $client;
    }

    public function getClient(): DotmailerClientInterface
    {
        if (!$this->client) {
            throw new \RuntimeException('DotmailerClientInterface is not injected');
        }

        return $this->client;
    }
}
