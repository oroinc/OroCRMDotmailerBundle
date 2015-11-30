<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Doctrine\Common\Persistence\ObjectManager;

use JMS\JobQueueBundle\Entity\Job;

use OroCRM\Bundle\DotmailerBundle\Command\ContactsExportCommand;

class LoadJobData extends AbstractFixture
{
    protected $data = [
        [
            'command' => ContactsExportCommand::NAME,
            'state' => Job::STATE_RUNNING,
            'reference' => 'orocrm_dotmailer.job.export.running'
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $reflection = new \ReflectionClass('JMS\JobQueueBundle\Entity\Job');

        foreach ($this->data as $item) {
            $job = new Job($item['command']);

            /**
             * Could not freely set state through setter
             */
            $stateProperty = $reflection->getProperty('state');
            $stateProperty->setAccessible(true);
            $stateProperty->setValue($job, $item['state']);

            $manager->persist($job);
            $this->setReference($item['reference'], $job);
        }

        $manager->flush();
    }
}
