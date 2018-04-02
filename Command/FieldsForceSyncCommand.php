<?php

namespace Oro\Bundle\DotmailerBundle\Command;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\DotmailerBundle\Model\SyncManager;
use Oro\Bundle\DotmailerBundle\Provider\ChannelType;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

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
        return $this->container->get('oro_dotmailer.manager.sync_manager');
    }

    /**
     * @return ChannelRepository
     */
    protected function getIntegrationRepository()
    {
        /** @var RegistryInterface $doctrine */
        $doctrine = $this->container->get('doctrine');

        return $doctrine->getRepository(Integration::class);
    }
}
