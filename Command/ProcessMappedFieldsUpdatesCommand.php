<?php
declare(strict_types=1);

namespace Oro\Bundle\DotmailerBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CronBundle\Command\CronCommandActivationInterface;
use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Oro\Bundle\DotmailerBundle\Entity\ChangedFieldLog;
use Oro\Bundle\DotmailerBundle\Processor\MappedFieldsChangeProcessor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Processes changed mapped entity field log and marks affected contacts for export.
 */
class ProcessMappedFieldsUpdatesCommand extends Command implements
    CronCommandScheduleDefinitionInterface,
    CronCommandActivationInterface
{
    /** @var string */
    protected static $defaultName = 'oro:cron:dotmailer:mapped-fields-updates:process';

    private ManagerRegistry $doctrine;
    private MappedFieldsChangeProcessor $processor;

    public function __construct(ManagerRegistry $doctrine, MappedFieldsChangeProcessor $processor)
    {
        parent::__construct();
        $this->doctrine = $doctrine;
        $this->processor = $processor;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultDefinition(): string
    {
        return '*/5 * * * *';
    }

    /**
     * {@inheritDoc}
     */
    public function isActive(): bool
    {
        $count = $this->doctrine->getRepository(ChangedFieldLog::class)
            ->createQueryBuilder('cl')
            ->select('COUNT(cl.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return ($count > 0);
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this->setDescription(
            'Processes changed mapped entity field log and marks affected contacts for export.'
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

        $output->writeln('Start queue processing');
        $this->processor->processFieldChangesQueue();

        $output->writeln('Completed');

        return 0;
    }
}
