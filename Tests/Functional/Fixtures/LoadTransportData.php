<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\AbstractFixture as BaseAbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DotmailerBundle\Entity\DotmailerTransport;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadTransportData extends BaseAbstractFixture implements ContainerAwareInterface
{
    /**
     * @var array
     */
    protected $data = [
        [
            'username' => 'John',
            'password' => 'Johns password',
            'clientId' => 'Johns client id',
            'clientKey' => 'Johns client key',
            'reference' => 'oro_dotmailer.transport.first'
        ],
        [
            'username' => 'John',
            'password' => 'Johns password',
            'clientId' => 'Johns client id',
            'clientKey' => 'Johns client key',
            'reference' => 'oro_dotmailer.transport.second'
        ],
        [
            'username' => 'John',
            'password' => 'Johns password',
            'clientId' => 'Johns client id',
            'clientKey' => 'Johns client key',
            'reference' => 'oro_dotmailer.transport.third'
        ],
        [
            'username' => 'John',
            'password' => 'Johns password',
            'clientId' => 'Johns client id',
            'clientKey' => 'Johns client key',
            'reference' => 'oro_dotmailer.transport.fourth'
        ],
        [
            'username' => 'John',
            'password' => 'Johns password',
            'clientId' => 'Johns client id',
            'clientKey' => 'Johns client key',
            'reference' => 'oro_dotmailer.transport.fifth'
        ]
    ];

    /**
     * @var SymmetricCrypterInterface
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
            $transport->setClientId($item['clientId']);
            $transport->setClientKey($this->encoder->encryptData($item['clientKey']));

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
        $this->encoder = $container->get('oro_security.encoder.default');
    }
}
