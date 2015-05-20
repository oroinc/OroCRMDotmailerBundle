<?php

namespace OroCRM\Bundle\DotmailerBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\IntegrationBundle\Command\AbstractSyncCronCommand;
use Oro\Bundle\IntegrationBundle\Provider\ReverseSyncProcessor;
use Oro\Component\Log\OutputLogger;

use OroCRM\Bundle\DotmailerBundle\Provider\ChannelType;
use OroCRM\Bundle\DotmailerBundle\Provider\Connector\ContactConnector;

class ContactsExportCommand extends AbstractSyncCronCommand
{
    const NAME = 'oro:cron:dotmailer:export';

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

        $em = $this->getService('doctrine.orm.entity_manager');
        $channels = $em->getRepository('OroIntegrationBundle:Channel')
            ->findBy(['type' => ChannelType::TYPE, 'enabled' => true]);

        foreach ($channels as $channel) {
            $this->getReverseSyncProcessor()->process($channel, ContactConnector::TYPE, []);
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
}
