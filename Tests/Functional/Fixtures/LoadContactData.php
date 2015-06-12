<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroCRM\Bundle\ContactBundle\Entity\Contact;
use OroCRM\Bundle\ContactBundle\Entity\ContactEmail;

class LoadContactData extends AbstractFixture implements DependentFixtureInterface
{
    protected $data = [
        [
            'firstName'    => 'Daniel',
            'lastName'     => 'Case',
            'email'        => 'daniel.case@example.com',
            'organization' => 'orocrm_dotmailer.organization.foo',
            'owner'        => 'orocrm_dotmailer.user.john.doe',
            'reference'    => 'orocrm_dotmailer.orocrm_contact.daniel.case',
        ],
        [
            'firstName'    => 'John',
            'lastName'     => 'Case',
            'email'        => 'john.case@example.com',
            'organization' => 'orocrm_dotmailer.organization.foo',
            'owner'        => 'orocrm_dotmailer.user.john.doe',
            'reference'    => 'orocrm_dotmailer.orocrm_contact.john.case',
        ],
        [
            'firstName'    => 'Jack',
            'lastName'     => 'Case',
            'email'        => 'jack.case@example.com',
            'organization' => 'orocrm_dotmailer.organization.foo',
            'owner'        => 'orocrm_dotmailer.user.john.doe',
            'reference'    => 'orocrm_dotmailer.orocrm_contact.jack.case',
        ],
        [
            'firstName'    => 'Alex',
            'lastName'     => 'Case',
            'email'        => 'alex.case@example.com',
            'organization' => 'orocrm_dotmailer.organization.foo',
            'owner'        => 'orocrm_dotmailer.user.john.doe',
            'reference'    => 'orocrm_dotmailer.orocrm_contact.alex.case',
        ],
        [
            'firstName'    => 'Allen',
            'lastName'     => 'Case',
            'email'        => 'allen.case@example.com',
            'organization' => 'orocrm_dotmailer.organization.foo',
            'owner'        => 'orocrm_dotmailer.user.john.doe',
            'reference'    => 'orocrm_dotmailer.orocrm_contact.allen.case',
        ],
        [
            'firstName'    => 'Without email',
            'lastName'     => 'Case',
            'organization' => 'orocrm_dotmailer.organization.foo',
            'owner'        => 'orocrm_dotmailer.user.john.doe',
            'reference'    => 'orocrm_dotmailer.orocrm_contact.without_email.case',
        ],
        [
            'firstName'    => 'John',
            'lastName'     => 'Smith',
            'email'        => 'john.smith@example.com',
            'organization' => 'orocrm_dotmailer.organization.foo',
            'owner'        => 'orocrm_dotmailer.user.john.doe',
            'reference'    => 'orocrm_dotmailer.orocrm_contact.john.smith',
        ],
        [
            'firstName'    => 'Nick',
            'lastName'     => 'Case',
            'email'        => 'nick.case@example.com',
            'organization' => 'orocrm_dotmailer.organization.foo',
            'owner'        => 'orocrm_dotmailer.user.john.doe',
            'reference'    => 'orocrm_dotmailer.orocrm_contact.nick.case',
        ],
        [
            'firstName'    => 'Mike',
            'lastName'     => 'Case',
            'email'        => 'mike.case@example.com',
            'organization' => 'orocrm_dotmailer.organization.foo',
            'owner'        => 'orocrm_dotmailer.user.john.doe',
            'reference'    => 'orocrm_dotmailer.orocrm_contact.mike.case',
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $data) {
            $contact = new Contact();
            $this->resolveReferenceIfExist($data, 'owner');
            $this->resolveReferenceIfExist($data, 'organization');
            $this->setEntityPropertyValues($contact, $data, ['reference', 'email']);

            if (!empty($data['email'])) {
                $email = new ContactEmail();
                $email->setEmail($data['email']);
                $email->setPrimary(true);
                $contact->addEmail($email);
            }

            $this->addReference($data['reference'], $contact);
            $manager->persist($contact);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadUserData',
        ];
    }
}
