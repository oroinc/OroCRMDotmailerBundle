<?php

namespace Oro\Bundle\DotmailerBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\DotmailerBundle\Entity\ChangedFieldLog;
use Oro\Bundle\DotmailerBundle\Processor\MappedFieldsChangeProcessor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Process the queue of changed mapped entities fields and mark corresponding contacts for export
 */
class ProcessMappedFieldsUpdatesCommand extends Command implements CronCommandInterface
{
    /** @var string */
    protected static $defaultName = 'oro:cron:dotmailer:mapped-fields-updates:process';

    /** @var ManagerRegistry */
    private $registry;

    /** @var MappedFieldsChangeProcessor */
    private $processor;

    /**
     * @param ManagerRegistry $registry
     * @param MappedFieldsChangeProcessor $processor
     */
    public function __construct(ManagerRegistry $registry, MappedFieldsChangeProcessor $processor)
    {
        parent::__construct();

        $this->registry = $registry;
        $this->processor = $processor;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Process the queue of changed mapped entities fields ' .
                'and mark corresponding contacts for export');
    }

    /**
     * {@inheritdoc}
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
