<?php

namespace OroCRM\Bundle\DotmailerBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use OroCRM\Bundle\DotmailerBundle\Model\SyncManager;

class FieldsForceSyncCommand extends Command implements CronCommandInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

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
    protected function configure()
    {
        $this
            ->setName('oro:cron:dotmailer:force-fields-sync')
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
        $this->getManager()->forceMarkEntityUpdate();
        $output->writeln('Completed');
    }

    /**
     * @return SyncManager
     */
    private function getManager()
    {
        return $this->container->get('orocrm_dotmailer.manager.sync_manager');
    }
}
