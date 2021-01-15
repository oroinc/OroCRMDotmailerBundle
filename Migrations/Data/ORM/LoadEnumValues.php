<?php

namespace Oro\Bundle\DotmailerBundle\Migrations\Data\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Entity\AddressBookContactsExport;
use Oro\Bundle\DotmailerBundle\Entity\Campaign;
use Oro\Bundle\DotmailerBundle\Entity\Contact;

class LoadEnumValues extends AbstractEnumFixture
{
    /** @var array */
    protected $enumData = [
        'dm_cmp_reply_action' => [
            Campaign::REPLY_ACTION_UNSET                        => 'Unset',
            Campaign::REPLY_ACTION_WEBMAILFORWARD               => 'WebMailForward',
            Campaign::REPLY_ACTION_WEBMAIL                      => 'Webmail',
            Campaign::REPLY_ACTION_DELETE                       => 'Delete',
            Campaign::REPLY_ACTION_NOTAVAILABLEINTHISVERSION    => 'NotAvailableInThisVersion',
        ],
        'dm_cmp_status' => [
            Campaign::STATUS_UNSENT                             => 'Unsent',
            Campaign::STATUS_SENDING                            => 'Sending',
            Campaign::STATUS_SENT                               => 'Sent',
            Campaign::STATUS_PAUSED                             => 'Paused',
            Campaign::STATUS_CANCELLED                          => 'Cancelled',
            Campaign::STATUS_REQUIRESSYSTEMAPPROVAL             => 'RequiresSystemApproval',
            Campaign::STATUS_REQUIRESSMSAPPROVAL                => 'RequiresSMSApproval',
            Campaign::STATUS_REQUIRESWORKFLOWAPPROVAL           => 'RequiresWorkflowApproval',
            Campaign::STATUS_TRIGGERED                          => 'Triggered',
            Campaign::STATUS_NOTAVAILABLEINTHISVERSION          => 'NotAvailableInThisVersion',
        ],
        'dm_ab_visibility' => [
            AddressBook::VISIBILITY_PRIVATE                     => 'Private',
            AddressBook::VISIBILITY_PUBLIC                      => 'Public',
            AddressBook::VISIBILITY_NOTAVAILABLEINTHISVERSION   => 'NotAvailableInThisVersion',
        ],
        'dm_cnt_opt_in_type' => [
            Contact::OPT_IN_TYPE_UNKNOWN                        => 'Unknown',
            Contact::OPT_IN_TYPE_SINGLE                         => 'Single',
            Contact::OPT_IN_TYPE_DOUBLE                         => 'Double',
            Contact::OPT_IN_TYPE_VERIFIEDDOUBLE                 => 'VerifiedDouble',
            Contact::OPT_IN_TYPE_NOTAVAILABLEINTHISVERSION      => 'NotAvailableInThisVersion',
        ],
        'dm_cnt_email_type' => [
            Contact::EMAIL_TYPE_PLAINTEXT                       => 'PlainText',
            Contact::EMAIL_TYPE_HTML                            => 'Html',
            Contact::EMAIL_TYPE_NOTAVAILABLEINTHISVERSION       => 'NotAvailableInThisVersion',
        ],
        'dm_cnt_status' => [
            Contact::STATUS_SUBSCRIBED                          => 'Subscribed',
            Contact::STATUS_UNSUBSCRIBED                        => 'Unsubscribed',
            Contact::STATUS_SOFTBOUNCED                         => 'SoftBounced',
            Contact::STATUS_HARDBOUNCED                         => 'HardBounced',
            Contact::STATUS_ISPCOMPLAINED                       => 'IspComplained',
            Contact::STATUS_MAILBLOCKED                         => 'MailBlocked',
            Contact::STATUS_PENDINGOPTIN                        => 'PendingOptIn',
            Contact::STATUS_DIRECTCOMPLAINT                     => 'DirectComplaint',
            Contact::STATUS_DELETED                             => 'Deleted',
            Contact::STATUS_SHAREDSUPPRESSION                   => 'SharedSuppression',
            Contact::STATUS_SUPPRESSED                          => 'Suppressed',
            Contact::STATUS_NOTALLOWED                          => 'NotAllowed',
            Contact::STATUS_DOMAINSUPPRESSION                   => 'DomainSuppression',
            Contact::STATUS_NOMXRECORD                          => 'NoMxRecord',
            Contact::STATUS_NOTAVAILABLEINTHISVERSION           => 'NotAvailableInThisVersion',
        ],
        'dm_import_status' => [
            AddressBookContactsExport::STATUS_NOT_FINISHED                   => 'NotFinished',
            AddressBookContactsExport::STATUS_FINISH                         => 'Finished',
            AddressBookContactsExport::STATUS_REJECTED_BY_WATCHDOG           => 'RejectedByWatchdog',
            AddressBookContactsExport::STATUS_INVALID_FILE_FORMAT            => 'InvalidFileFormat',
            AddressBookContactsExport::STATUS_UNKNOWN                        => 'Unknown',
            AddressBookContactsExport::STATUS_FAILED                         => 'Failed',
            AddressBookContactsExport::STATUS_EXCEEDS_ALLOWED_CONTACT_LIMIT  => 'ExceedsAllowedContactLimit',
            AddressBookContactsExport::STATUS_NOT_AVAILABLE_IN_THIS_VERSION  => 'NotAvailableInThisVersion',
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var EntityManagerInterface $manager */
        $this->loadEnumValues($this->enumData, $manager);
    }
}
