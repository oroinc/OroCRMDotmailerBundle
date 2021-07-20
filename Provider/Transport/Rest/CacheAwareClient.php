<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Transport\Rest;

use Oro\Bundle\DotmailerBundle\Provider\Transport\CacheProviderAwareTrait;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;

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
    const REDELIVERED_DELAY_TIME = 900;

    /** @var DotmailerClientInterface */
    private $client;

    /** @var string */
    private $namespace;

    /**
     * {@inheritdoc}
     */
    public function __construct($username, $password = null)
    {
        $this->namespace = $username;
    }

    /**
     * {@inheritdoc}
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        $this->getClient()->setLogger($logger);
    }

    /** @return LoggerInterface */
    public function getLogger()
    {
        if (!$this->logger) {
            $this->logger = new NullLogger();
        }

        return $this->logger;
    }

    /**
     * {@inheritdoc}
     */
    public function setBaseUrl($url)
    {
        $this->getClient()->setBaseUrl($url);
    }

    /**
     * {@inheritdoc}
     */
    public function execute($paramArr, $responses = [])
    {
        list(, $requestMethod) = array_pad(array_values($paramArr), 2, null);

        if (in_array($requestMethod, [Request::METHOD_GET, Request::METHOD_HEAD, Request::METHOD_OPTIONS], true)) {
            return $this->processSafeRequest($paramArr, $responses);
        }

        return $this->processNonSafeRequest($paramArr, $responses);
    }

    /**
     * @param array $paramArr
     * @param array $responses
     * @return mixed
     */
    private function processSafeRequest(array $paramArr, array $responses = [])
    {
        $cacheKey = $this->getCacheKey($paramArr);
        if ($result = $this->getCache()->fetch($cacheKey)) {
            $this->getLogger()->debug('[DM] Data found in cache', $paramArr);

            return $result;
        }

        $result = $this->getClient()->execute($paramArr, $responses);

        $this->getCache()->save($cacheKey, $result, static::REDELIVERED_DELAY_TIME);
        $this->getLogger()->debug('[DM] Save data cache', $paramArr);

        return $result;
    }

    /**
     * @param array $paramArr
     * @param array $responses
     * @return mixed
     */
    private function processNonSafeRequest(array $paramArr, array $responses = [])
    {
        $this->getCache()->deleteAll();
        $this->getLogger()->debug('[DM] Delete data from cache', $paramArr);

        return $this->getClient()->execute($paramArr, $responses);
    }

    private function getCacheKey(array $paramArr = [])
    {
        list($requestUrl) = array_pad(array_values($paramArr), 1, null);
        return $this->namespace . md5($requestUrl);
    }

    public function setClient(DotmailerClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @return DotmailerClientInterface
     */
    public function getClient()
    {
        if (!$this->client) {
            throw new \RuntimeException('DotmailerClientInterface is not injected');
        }

        return $this->client;
    }
}
