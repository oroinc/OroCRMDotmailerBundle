<?php

namespace Oro\Bundle\DotmailerBundle\Command;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\DotmailerBundle\Processor\MappedFieldsChangeProcessor;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class ProcessMappedFieldsUpdatesCommand extends Command implements CronCommandInterface, ContainerAwareInterface
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
            ->setName('oro:cron:dotmailer:mapped-fields-updates:process')
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
        $this->getProcessor()->processFieldChangesQueue();

        $output->writeln('Completed');
    }

    /**
     * @return MappedFieldsChangeProcessor
     */
    private function getProcessor()
    {
        return $this->container->get('oro_dotmailer.processor.mapped_fields_change');
    }
}
