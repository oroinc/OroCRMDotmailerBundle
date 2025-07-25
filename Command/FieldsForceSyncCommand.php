<?php

declare(strict_types=1);

namespace Oro\Bundle\DotmailerBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CronBundle\Command\CronCommandActivationInterface;
use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Oro\Bundle\DotmailerBundle\Model\SyncManager;
use Oro\Bundle\DotmailerBundle\Provider\ChannelType;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Marks address book contacts as updated to ensure that updated virtual field values are synced to dotdigital.
 */
#[AsCommand(
    name: 'oro:cron:dotmailer:force-fields-sync',
    description: 'Marks address book contacts as updated to ensure virtual fields sync.'
)]
class FieldsForceSyncCommand extends Command implements
    CronCommandScheduleDefinitionInterface,
    CronCommandActivationInterface
{
    private ManagerRegistry $doctrine;
    private SyncManager $syncManager;

    public function __construct(ManagerRegistry $doctrine, SyncManager $syncManager)
    {
        parent::__construct();
        $this->doctrine = $doctrine;
        $this->syncManager = $syncManager;
    }

    #[\Override]
    public function getDefaultDefinition(): string
    {
        return '0 1 * * *';
    }

    #[\Override]
    public function isActive(): bool
    {
        $count = $this->getIntegrationRepository()->countActiveIntegrations(ChannelType::TYPE);

        return ($count > 0);
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    protected function configure()
    {
        $this
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command marks address book contacts as updated to ensure that updated
virtual field values are synced to dotdigital.

  <info>php %command.full_name%</info>

HELP
            );
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    #[\Override]
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($output->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE) {
            $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        }

        $output->writeln('Start update of address book contacts');
        $this->syncManager->forceMarkEntityUpdate();
        $output->writeln('Completed');

        return Command::SUCCESS;
    }

    protected function getIntegrationRepository(): ChannelRepository
    {
        return $this->doctrine->getRepository(Integration::class);
    }
}
