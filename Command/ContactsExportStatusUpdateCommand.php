<?php

namespace OroCRM\Bundle\DotmailerBundle\Command;

use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use OroCRM\Bundle\DotmailerBundle\Async\Topics;
use OroCRM\Bundle\DotmailerBundle\Provider\ChannelType;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class ContactsExportStatusUpdateCommand extends Command implements CronCommandInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getDefaultDefinition()
    {
        return '*/5 * * * *';
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:cron:dotmailer:export-status:update')
            ->setDescription('Updates status of Dotmailer\'s contacts export operations.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($output->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE) {
            $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        }

        $output->writeln('Send export contacts status update for channel:');

        $channels = $this->getChannelRepository()->findBy(['type' => ChannelType::TYPE, 'enabled' => true]);
        foreach ($channels as $channel) {
            /** @var Channel $channel */

            $output->writeln(sprintf('Channel "%s"', $channel->getId()));

            $message = new Message();
            $message->setPriority(MessagePriority::VERY_LOW);
            $message->setBody([
                'integrationId' => $channel->getId(),
            ]);

            $this->getMessageProducer()->send(Topics::EXPORT_CONTACTS_STATUS_UPDATE, $message);
        }

        $output->writeln('Completed');
    }

    /**
     * @return ObjectRepository
     */
    protected function getChannelRepository()
    {
        /** @var RegistryInterface $doctrine */
        $doctrine = $this->container->get('doctrine');

        return $doctrine->getRepository(Channel::class);
    }

    /**
     * @return MessageProducerInterface
     */
    private function getMessageProducer()
    {
        return $this->container->get('oro_message_queue.message_producer');
    }
}
