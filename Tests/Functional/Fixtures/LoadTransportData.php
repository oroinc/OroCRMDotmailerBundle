<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroCRM\Bundle\DotmailerBundle\Entity\DotmailerTransport;

class LoadTransportData extends AbstractFixture
{
    /**
     * @var array
     */
    protected $data = [
        [
            'username' => 'John',
            'password' => 'Johns password',
            'reference' => 'orocrm_dotmailer.transport.first'
        ],
        [
            'username' => 'John',
            'password' => 'Johns password',
            'reference' => 'orocrm_dotmailer.transport.second'
        ],
        [
            'username' => 'John',
            'password' => 'Johns password',
            'reference' => 'orocrm_dotmailer.transport.third'
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $item) {
            $transport = new DotmailerTransport();
            $transport->setUsername($item['username']);
            $transport->setUsername($item['password']);

            $manager->persist($transport);
            $this->setReference($item['reference'], $transport);
        }

        $manager->flush();
    }
}
