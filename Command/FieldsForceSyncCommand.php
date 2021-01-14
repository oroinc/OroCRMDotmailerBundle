<?php
declare(strict_types=1);

namespace Oro\Bundle\DotmailerBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CronBundle\Command\CronCommandInterface;
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
class FieldsForceSyncCommand extends Command implements CronCommandInterface
{
    /** @var string */
    protected static $defaultName = 'oro:cron:dotmailer:force-fields-sync';

    private ManagerRegistry $registry;
    private SyncManager $syncManager;

    public function __construct(ManagerRegistry $registry, SyncManager $syncManager)
    {
        parent::__construct();

        $this->registry = $registry;
        $this->syncManager = $syncManager;
    }

    public function getDefaultDefinition()
    {
        return '0 1 * * *';
    }

    public function isActive()
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
    }

    protected function getIntegrationRepository(): ChannelRepository
    {
        return $this->registry->getRepository(Integration::class);
    }
}
