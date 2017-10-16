<?php

namespace Oro\Bundle\DotmailerBundle\Async;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Psr\Log\LoggerInterface;

use Oro\Bundle\IntegrationBundle\Authentication\Token\IntegrationTokenAwareTrait;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;

class ContactsClearProcessor implements MessageProcessorInterface, ContainerAwareInterface, TopicSubscriberInterface
{
    use ContainerAwareTrait;
    use IntegrationTokenAwareTrait;

    /**
     * @var JobRunner
     */
    protected $jobRunner;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     * @param JobRunner $jobRunner
     * @param LoggerInterface $logger;
     */
    public function __construct(
        ManagerRegistry $registry,
        JobRunner $jobRunner,
        LoggerInterface $logger
    ) {
        $this->registry = $registry;
        $this->jobRunner = $jobRunner;
        $this->logger = $logger;
    }
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::DM_CONTACTS_CLEANER];
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());
        $body = array_replace_recursive([
            'integration_id' => null
        ], $body);

        if (! $body['integration_id']) {
            $this->logger->critical('Invalid message: integration_id is empty', ['message' => $message]);
            return self::REJECT;
        }
        $em = $this->registry->getManager();

        /** @var Integration $integration */
        $integration = $em->find(Integration::class, $body['integration_id']);
        if (! $integration) {
            $this->logger->error(
                sprintf('Integration with id "%s" is not found', $body['integration_id']),
                ['message' => $message]
            );
            return self::REJECT;
        }

        if (! $integration->isEnabled()) {
            $this->logger->error(
                sprintf('Integration with id "%s" is not enabled', $body['integration_id']),
                ['message' => $message]
            );
            return self::REJECT;
        }

        $jobName = 'oro_dotmailer:contacts:clear:' . $body['integration_id'];
        $ownerId = $message->getMessageId();

        $result = $this
            ->jobRunner
            ->runUnique($ownerId, $jobName, function () use ($integration) {
                /**
                 * Remove contact drafts which was not fully exported to Dotmailer
                 */
                $this->registry->getRepository('OroDotmailerBundle:Contact')
                    ->bulkRemoveNotExportedContacts($integration);
                return true;
            });

        return $result ? self::ACK : self::REJECT;
    }
}
