<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;

use OroCRM\Bundle\DotmailerBundle\Entity\Contact;

class LoadDotmailerContactData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $data = [
        // contact for contact update test
        [
            'originId'  => 142,
            'email'     => 'test1@ex.com',
            'channel'   => 'orocrm_dotmailer.channel.second',
            'reference' => 'orocrm_dotmailer.contact.update_1',
            'createdAt' => 'first day of January 2008',
        ],
        [
            'originId'  => 143,
            'email'     => 'test2@ex.com',
            'channel'   => 'orocrm_dotmailer.channel.second',
            'reference' => 'orocrm_dotmailer.contact.update_2',
            'createdAt' => 'first day of January 2008',
        ],
    ];

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $userManager = $this->container->get('oro_user.manager');
        $admin = $userManager->findUserByEmail(LoadAdminUserData::DEFAULT_ADMIN_EMAIL);

        foreach ($this->data as $item) {
            $contact = new Contact();
            $contact->setOriginId($item['originId']);
            $contact->setOwner($admin->getOrganization());
            $contact->setChannel($this->getReference($item['channel']));

            if (!empty($item['email'])) {
                $contact->setEmail($item['email']);
            }

            if (!empty($item['createdAt'])) {
                $contact->setCreatedAt(new \DateTime($item['createdAt']));
            }

            $manager->persist($contact);
            $this->setReference($item['reference'], $contact);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadChannelData',
        ];
    }
}
