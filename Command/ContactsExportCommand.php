<?php

namespace OroCRM\Bundle\DotmailerBundle\Command;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\IntegrationBundle\Command\AbstractSyncCronCommand;
use Oro\Bundle\IntegrationBundle\Provider\ReverseSyncProcessor;
use Oro\Component\Log\OutputLogger;

use OroCRM\Bundle\ChannelBundle\Entity\Channel;
use OroCRM\Bundle\DotmailerBundle\Provider\ChannelType;
use OroCRM\Bundle\DotmailerBundle\Provider\Connector\ContactConnector;

class ContactsExportCommand extends AbstractSyncCronCommand
{
    const NAME = 'oro:cron:dotmailer:export';

    /**
     * @var ObjectManager
     */
    protected $em;

    /**
     * {@inheritdoc}
     */
    public function getDefaultDefinition()
    {
        return '*/5 * * * *';
    }

    /**
     * @var ReverseSyncProcessor
     */
    protected $reverseSyncProcessor;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Export contacts to Dotmailer');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new OutputLogger($output);
        $this->getContainer()->get('oro_integration.logger.strategy')->setLogger($logger);

        if ($this->isJobRunning(null)) {
            $logger->warning('Job already running. Terminating....');

            return;
        }

        $exportManager = $this->getContainer()->get('orocrm_dotmailer.export_manager');
        $this->em = $this->getContainer()->get('doctrine')->getManager();

        $channels = $this->em->getRepository('OroIntegrationBundle:Channel')
            ->findBy(['type' => ChannelType::TYPE, 'enabled' => true]);

        foreach ($channels as $channel) {
            //If previous export not finished and update export result not completed will check next channel
            if (!$exportManager->isExportFinished($channel) && !$exportManager->updateExportResults($channel)) {
                continue;
            }

            $this->removePreviousAddressBookContactsExport($channel);
            $this->getReverseSyncProcessor()->process($channel, ContactConnector::TYPE, []);
            $exportManager->updateExportResults($channel);
        }
    }

    /**
     * @return ReverseSyncProcessor
     */
    protected function getReverseSyncProcessor()
    {
        if (!$this->reverseSyncProcessor) {
            $this->reverseSyncProcessor = $this->getContainer()->get('oro_integration.reverse_sync.processor');
        }

        return $this->reverseSyncProcessor;
    }

    /**
     * @param Channel $channel
     */
    protected function removePreviousAddressBookContactsExport(Channel $channel)
    {
        $this->em->getRepository('OroCRMDotmailerBundle:AddressBookContactsExport')
            ->createQueryBuilder('abContactsExport')
            ->delete()
            ->where('abContactsExport.channel =:channel')
            ->getQuery()
            ->execute(['channel' => $channel]);
    }
}
