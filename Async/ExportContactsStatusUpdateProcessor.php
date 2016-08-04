<?php
namespace OroCRM\Bundle\DotmailerBundle\Async;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use OroCRM\Bundle\DotmailerBundle\Model\ExportManager;

class ExportContactsStatusUpdateProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var ExportManager
     */
    private $exportManager;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ExportManager $exportManager
     */
    public function __construct(DoctrineHelper $doctrineHelper, ExportManager $exportManager)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->exportManager = $exportManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        // TODO CRM-5838 unique job
        // TODO CRM-5838 message could be redelivered on dbal transport if run for a long time.

        $body = JSON::decode($message->getBody());
        $body = array_replace_recursive(['integrationId' => null], $body);

        if (false == $body['integrationId']) {
            throw new \LogicException('The message invalid. It must have integrationId set');
        }

        /** @var EntityManagerInterface $em */
        $em = $this->doctrineHelper->getEntityManagerForClass(Channel::class);

        /** @var Channel $channel */
        $channel = $em->find(Channel::class, $body['integrationId']);
        if (false == $channel) {
            return self::REJECT;
        }
        if (false == $channel->isEnabled()) {
            return self::REJECT;
        }

        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        /**
         * If previous export was not finished we need to update export results from Dotmailer.
         * If finished we need to process export faults reports
         */
        if (!$this->exportManager->isExportFinished($channel)) {
            $this->exportManager->updateExportResults($channel);
        } elseif (!$this->exportManager->isExportFaultsProcessed($channel)) {
            $this->exportManager->processExportFaults($channel);
        }

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::EXPORT_CONTACTS_STATUS_UPDATE];
    }
}
