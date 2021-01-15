<?php
declare(strict_types=1);

namespace Oro\Bundle\DotmailerBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\DotmailerBundle\Entity\ChangedFieldLog;
use Oro\Bundle\DotmailerBundle\Processor\MappedFieldsChangeProcessor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Processes changed mapped entity field log and marks affected contacts for export.
 */
class ProcessMappedFieldsUpdatesCommand extends Command implements CronCommandInterface
{
    /** @var string */
    protected static $defaultName = 'oro:cron:dotmailer:mapped-fields-updates:process';

    private ManagerRegistry $registry;
    private MappedFieldsChangeProcessor $processor;

    public function __construct(ManagerRegistry $registry, MappedFieldsChangeProcessor $processor)
    {
        parent::__construct();

        $this->registry = $registry;
        $this->processor = $processor;
    }

    public function getDefaultDefinition()
    {
        return '*/5 * * * *';
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        $count = $this->registry->getRepository(ChangedFieldLog::class)
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
    }
}
