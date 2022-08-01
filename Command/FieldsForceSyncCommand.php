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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Marks address book contacts as updated to ensure that updated virtual field values are synced to dotdigital.
 */
class FieldsForceSyncCommand extends Command implements
    CronCommandScheduleDefinitionInterface,
    CronCommandActivationInterface
{
    /** @var string */
    protected static $defaultName = 'oro:cron:dotmailer:force-fields-sync';

    private ManagerRegistry $doctrine;
    private SyncManager $syncManager;

    public function __construct(ManagerRegistry $doctrine, SyncManager $syncManager)
    {
        parent::__construct();
        $this->doctrine = $doctrine;
        $this->syncManager = $syncManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultDefinition(): string
    {
        return '0 1 * * *';
    }

    /**
     * {@inheritDoc}
     */
    public function isActive(): bool
    {
        $count = $this->getIntegrationRepository()->countActiveIntegrations(ChannelType::TYPE);

        return ($count > 0);
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->setDescription('Marks address book contacts as updated to ensure virtual fields sync.')
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
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($output->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE) {
            $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        }

        $output->writeln('Start update of address book contacts');
        $this->syncManager->forceMarkEntityUpdate();
        $output->writeln('Completed');

        return 0;
    }

    protected function getIntegrationRepository(): ChannelRepository
    {
        return $this->doctrine->getRepository(Integration::class);
    }
}
