<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\AbstractFixture as BaseAbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;
use OroCRM\Bundle\DotmailerBundle\Entity\DotmailerTransport;

class LoadTransportData extends BaseAbstractFixture implements ContainerAwareInterface
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
        ],
        [
            'username' => 'John',
            'password' => 'Johns password',
            'reference' => 'orocrm_dotmailer.transport.fourth'
        ]
    ];

    /**
     * @var Mcrypt
     */
    protected $encoder;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $item) {
            $transport = new DotmailerTransport();
            $transport->setUsername($item['username']);
            $transport->setPassword($this->encoder->encryptData($item['password']));

            $manager->persist($transport);
            $this->setReference($item['reference'], $transport);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->encoder = $container->get('oro_security.encoder.mcrypt');
    }
}
