<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures;

use Symfony\Component\DependencyInjection\ContainerInterface;

use DotMailer\Api\DataTypes\ApiContactStatuses;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroCRM\Bundle\DotmailerBundle\Entity\AddressBookContact;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use OroCRM\Bundle\DotmailerBundle\Entity\Contact;

class LoadDotmailerContactData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $data = [
        [
            'originId'     => 42,
            'channel'      => 'orocrm_dotmailer.channel.second',
            'email'        => 'first@mail.com',
            'status'       => ApiContactStatuses::SUBSCRIBED,
            'addressBooks' => ['orocrm_dotmailer.address_book.third'],
            'reference'    => 'orocrm_dotmailer.contact.first',
        ],
        [
            'originId'    => 147,
            'email'       => 'alex.case@example.com',
            'firstName'   => 'Test147',
            'lastName'    => 'Test147',
            'gender'      => 'male',
            'channel'     => 'orocrm_dotmailer.channel.second',
            'reference'   => 'orocrm_dotmailer.contact.alex_case.second_channel',
            'status'      => ApiContactStatuses::SUBSCRIBED,
            'opt_in_type' => Contact::OPT_IN_TYPE_SINGLE,
            'email_type'  => Contact::EMAIL_TYPE_PLAINTEXT
        ],
        [
            'originId'     => 42,
            'email'        => 'second@mail.com',
            'channel'      => 'orocrm_dotmailer.channel.third',
            'status'       => ApiContactStatuses::SUBSCRIBED,
            'addressBooks' => [
                'orocrm_dotmailer.address_book.third',
                'orocrm_dotmailer.address_book.fourth',
                'orocrm_dotmailer.address_book.six'
            ],
            'reference'    => 'orocrm_dotmailer.contact.second',
        ],
        [
            'originId'           => 13,
            'email'              => 'test_concurrent_statuses@mail.com',
            'channel'            => 'orocrm_dotmailer.channel.third',
            'status'             => ApiContactStatuses::SUBSCRIBED,
            'addressBooks'       => [
                'orocrm_dotmailer.address_book.third',
                'orocrm_dotmailer.address_book.fourth',
                'orocrm_dotmailer.address_book.six'
            ],
            'lastSubscribedDate' => '2015-10-11',
            'reference'          => 'orocrm_dotmailer.contact.test_concurrent_statuses',
        ],
        [
            'originId'     => 42,
            'email'        => 'second@mail.com',
            'channel'      => 'orocrm_dotmailer.channel.fourth',
            'status'       => ApiContactStatuses::SUBSCRIBED,
            'addressBooks' => [
                'orocrm_dotmailer.address_book.third',
                'orocrm_dotmailer.address_book.fourth',
                [
                    'addressBook'           =>  'orocrm_dotmailer.address_book.six',
                    'status'                =>  Contact::STATUS_SUBSCRIBED,
                    'exportOperationType'   =>  AddressBookContact::EXPORT_NEW_CONTACT
                ]
            ],
            'reference'    => 'orocrm_dotmailer.contact.add_contact_rejected',
        ],
        [
            'originId'           => 13,
            'email'              => 'test_concurrent_statuses@mail.com',
            'channel'            => 'orocrm_dotmailer.channel.fourth',
            'status'             => ApiContactStatuses::SUBSCRIBED,
            'addressBooks'       => [
                'orocrm_dotmailer.address_book.third',
                'orocrm_dotmailer.address_book.fourth',
                [
                    'addressBook'           =>  'orocrm_dotmailer.address_book.six',
                    'status'                =>  Contact::STATUS_SUBSCRIBED,
                    'exportOperationType'   =>  AddressBookContact::EXPORT_UPDATE_CONTACT
                ]
            ],
            'lastSubscribedDate' => '2015-10-11',
            'reference'          => 'orocrm_dotmailer.contact.update_contact_rejected',
        ],
        // contact for contact update test
        [
            'originId'     => 142,
            'email'        => 'test1@ex.com',
            'channel'      => 'orocrm_dotmailer.channel.fourth',
            'reference'    => 'orocrm_dotmailer.contact.update_1',
            'createdAt'    => 'first day of January 2008',
            'status'       => ApiContactStatuses::SUBSCRIBED,
            'addressBooks' => ['orocrm_dotmailer.address_book.fourth', 'orocrm_dotmailer.address_book.fifth'],
        ],
        [
            'originId'     => null,
            'email'        => 'test2@ex.com',
            'firstName'    => 'Test2',
            'lastName'     => 'Test2',
            'gender'       => 'female',
            'channel'      => 'orocrm_dotmailer.channel.fourth',
            'reference'    => 'orocrm_dotmailer.contact.update_2',
            'createdAt'    => 'first day of January 2008',
            'status'       => ApiContactStatuses::SUBSCRIBED,
            'addressBooks' => [
                'orocrm_dotmailer.address_book.fourth',
                [
                    'addressBook'           =>  'orocrm_dotmailer.address_book.six',
                    'status'                =>  Contact::STATUS_SUBSCRIBED,
                    'exportOperationType'   =>  AddressBookContact::EXPORT_ADD_TO_ADDRESS_BOOK
                ]
            ],
        ],
        [
            'originId'     => 144,
            'email'        => 'daniel.case@example.com',
            'firstName'    => 'Test144',
            'lastName'     => 'Test144',
            'gender'       => 'male',
            'channel'      => 'orocrm_dotmailer.channel.fourth',
            'reference'    => 'orocrm_dotmailer.contact.unsubscribed_from_ab',
            'status'       => ApiContactStatuses::SUBSCRIBED,
            'addressBooks' => [
                [
                    'addressBook'         => 'orocrm_dotmailer.address_book.fifth',
                    'status'              => Contact::STATUS_UNSUBSCRIBED,
                    'marketing_list_item' => 'orocrm_dotmailer.orocrm_contact.daniel.case'
                ]
            ],
        ],
        [
            'originId'     => 145,
            'email'        => 'john.smith@example.com',
            'firstName'    => 'Test145',
            'lastName'     => 'Test145',
            'gender'       => 'male',
            'channel'      => 'orocrm_dotmailer.channel.fourth',
            'reference'    => 'orocrm_dotmailer.contact.removed',
            'status'       => ApiContactStatuses::SUBSCRIBED,
            'addressBooks' => [
                [
                    'addressBook'         => 'orocrm_dotmailer.address_book.fifth',
                    'status'              => Contact::STATUS_SUBSCRIBED,
                    'marketing_list_item' => 'orocrm_dotmailer.orocrm_contact.john.smith'
                ]
            ],
        ],
        [
            'originId'     => 146,
            'email'        => 'john.case@example.com',
            'firstName'    => 'Test146',
            'lastName'     => 'Test146',
            'gender'       => 'male',
            'channel'      => 'orocrm_dotmailer.channel.fourth',
            'reference'    => 'orocrm_dotmailer.contact.synced',
            'status'       => ApiContactStatuses::SUBSCRIBED,
            'opt_in_type'  => Contact::OPT_IN_TYPE_SINGLE,
            'email_type'   => Contact::EMAIL_TYPE_PLAINTEXT,
            'addressBooks' => [
                [
                    'addressBook'         => 'orocrm_dotmailer.address_book.fifth',
                    'status'              => Contact::STATUS_SUBSCRIBED,
                    /** check situation where email is presented in marketing list but item is new */
                    'marketing_list_item' => 'orocrm_dotmailer.orocrm_contact.john.smith'
                ]
            ],
        ],
        [
            'originId'    => 147,
            'email'       => 'alex.case@example.com',
            'firstName'   => 'Test147',
            'lastName'    => 'Test147',
            'gender'      => 'male',
            'channel'     => 'orocrm_dotmailer.channel.fourth',
            'reference'   => 'orocrm_dotmailer.contact.alex_case',
            'status'      => ApiContactStatuses::SUBSCRIBED,
            'opt_in_type' => Contact::OPT_IN_TYPE_SINGLE,
            'email_type'  => Contact::EMAIL_TYPE_PLAINTEXT
        ],
        [
            'originId'     => 148,
            'email'        => 'allen.case@example.com',
            'firstName'    => 'Test148',
            'lastName'     => 'Test148',
            'gender'       => 'male',
            'channel'      => 'orocrm_dotmailer.channel.fourth',
            'reference'    => 'orocrm_dotmailer.contact.allen_case',
            'status'       => ApiContactStatuses::SUBSCRIBED,
            'opt_in_type'  => Contact::OPT_IN_TYPE_SINGLE,
            'email_type'   => Contact::EMAIL_TYPE_PLAINTEXT,
            'addressBooks' => [
                [
                    'addressBook'           =>  'orocrm_dotmailer.address_book.six',
                    'status'                =>  Contact::STATUS_SUBSCRIBED,
                    'exportOperationType'   =>  AddressBookContact::EXPORT_UPDATE_CONTACT
                ],
                [
                    'addressBook' => 'orocrm_dotmailer.address_book.fifth',
                    'status'      => Contact::STATUS_SUBSCRIBED
                ]
            ],
        ],
        [
            'originId'     => 149,
            'email'        => 'nick.case@example.com',
            'firstName'    => 'Test149',
            'lastName'     => 'Test149',
            'gender'       => 'male',
            'channel'      => 'orocrm_dotmailer.channel.fourth',
            'reference'    => 'orocrm_dotmailer.contact.removed_as_unsubscribed',
            'status'       => ApiContactStatuses::SUBSCRIBED,
            'addressBooks' => [
                [
                    'addressBook'         => 'orocrm_dotmailer.address_book.fifth',
                    'status'              => Contact::STATUS_SUBSCRIBED,
                    'marketing_list_item' => 'orocrm_dotmailer.orocrm_contact.nick.case'
                ]
            ]
        ],
        [
            'originId'     => 150,
            'email'        => 'mike.case@example.com',
            'firstName'    => 'Test150',
            'lastName'     => 'Test150',
            'gender'       => 'male',
            'channel'      => 'orocrm_dotmailer.channel.fourth',
            'reference'    => 'orocrm_dotmailer.contact.removed_from_marketing_list',
            'status'       => ApiContactStatuses::SUBSCRIBED,
            'addressBooks' => [
                [
                    'addressBook'         => 'orocrm_dotmailer.address_book.fifth',
                    'status'              => Contact::STATUS_SUBSCRIBED,
                    'marketing_list_item' => 'orocrm_dotmailer.orocrm_contact.mike.case'
                ]
            ]
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
            $contact->setOwner($admin->getOrganization());
            $this->resolveReferenceIfExist($item, 'channel');

            if (!empty($item['addressBooks'])) {
                $item = $this->resolveAddressBookRelations($item, $contact, $manager);
            }


            if (!empty($item['createdAt'])) {
                $item['createdAt'] = new \DateTime($item['createdAt']);
            }
            if (!empty($item['lastSubscribedDate'])) {
                $item['lastSubscribedDate'] = new \DateTime($item['lastSubscribedDate']);
            }

            $item['status'] = $this->findEnum('dm_cnt_status', $item['status']);
            if (isset($item['opt_in_type'])) {
                $item['opt_in_type'] = $this->findEnum('dm_cnt_opt_in_type', $item['opt_in_type']);
            }
            if (isset($item['email_type'])) {
                $item['email_type'] = $this->findEnum('dm_cnt_email_type', $item['email_type']);
            }
            $this->setEntityPropertyValues(
                $contact,
                $item,
                [
                    'addressBooks',
                    'reference'
                ]
            );

            $manager->persist($contact);

            $this->setReference($item['reference'], $contact);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadChannelData',
            'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadAddressBookData',
            'OroCRM\Bundle\DotmailerBundle\Tests\Functional\Fixtures\LoadContactData',
        ];
    }

    /**
     * @param array         $item
     * @param Contact       $contact
     * @param ObjectManager $manager
     *
     * @return mixed
     */
    protected function resolveAddressBookRelations(array $item, Contact $contact, ObjectManager $manager)
    {
        foreach ($item['addressBooks'] as $data) {
            $addressBookContact = new AddressBookContact();
            $status = $this->findEnum('dm_cnt_status', Contact::STATUS_SUBSCRIBED);
            if (is_scalar($data)) {
                $addressBook = $this->getReference($data);
            } else {
                $addressBook = $this->getReference($data['addressBook']);

                if (isset($data['marketing_list_item'])) {
                    $marketingListItem = $this->getReference($data['marketing_list_item']);
                    $addressBookContact->setMarketingListItemId($marketingListItem->getId());
                    $addressBookContact->setMarketingListItemClass(
                        'OroCRM\Bundle\ContactBundle\Entity\Contact'
                    );
                }

                if (isset($data['status'])) {
                    $status = $this->findEnum('dm_cnt_status', $data['status']);
                }
            }

            $addressBookContact->setAddressBook($addressBook);
            $addressBookContact->setStatus($status);
            $addressBookContact->setChannel($item['channel']);
            if (empty($item['originId'])) {
                $addressBookContact->setScheduledForExport(true);
            }
            if (!empty($data['exportOperationType'])) {
                $operationType = $this->findEnum('dm_ab_cnt_exp_type', $data['exportOperationType']);
                $addressBookContact->setExportOperationType($operationType);
            }
            $contact->addAddressBookContact($addressBookContact);

            $manager->persist($addressBookContact);
        }

        return $item;
    }
}
