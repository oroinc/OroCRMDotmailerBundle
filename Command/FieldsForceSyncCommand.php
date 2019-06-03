<?php

namespace Oro\Bundle\DotmailerBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\DotmailerBundle\Model\SyncManager;
use Oro\Bundle\DotmailerBundle\Provider\ChannelType;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Mark address book contacts as updated to make sure updated field values are synced to Dotmailer
 */
class FieldsForceSyncCommand extends Command implements CronCommandInterface
{
    /** @var string */
    protected static $defaultName = 'oro:cron:dotmailer:force-fields-sync';

    /** @var ManagerRegistry */
    private $registry;

    /** @var SyncManager */
    private $syncManager;

    /**
     * @param SyncManager $syncManager
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry, SyncManager $syncManager)
    {
        parent::__construct();

        $this->registry = $registry;
        $this->syncManager = $syncManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultDefinition()
    {
        return '0 1 * * *';
    }

    /**
     * {@inheritdoc}
     */
    public function isActive()
    {
        $count = $this->getIntegrationRepository()->countActiveIntegrations(ChannelType::TYPE);

        return ($count > 0);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('If conditions are met, mark all address book contacts as updated '
                . 'to make sure updated virtual field values are synced to dotmailer');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($output->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE) {
            $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        }

        $output->writeln('Start update of address book contacts');
        $this->syncManager->forceMarkEntityUpdate();
        $output->writeln('Completed');
    }

    /**
     * @return ChannelRepository
     */
    protected function getIntegrationRepository(): ChannelRepository
    {
        return $this->registry->getRepository(Integration::class);
    }
}
