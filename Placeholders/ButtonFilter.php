<?php

namespace Oro\Bundle\DotmailerBundle\Placeholders;

use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Bundle\MarketingListBundle\Provider\ContactInformationFieldsProvider;

/**
 * Filters button placeholders based on Dotmailer configuration.
 */
class ButtonFilter
{
    /**
     * @var ContactInformationFieldsProvider
     */
    protected $fieldsProvider;

    public function __construct(ContactInformationFieldsProvider $fieldsProvider)
    {
        $this->fieldsProvider = $fieldsProvider;
    }

    /**
     * @param mixed $entity
     *
     * @return bool
     */
    public function isApplicable($entity)
    {
        if ($entity instanceof MarketingList) {
            return (bool)$this->fieldsProvider->getMarketingListTypedFields(
                $entity,
                ContactInformationFieldsProvider::CONTACT_INFORMATION_SCOPE_EMAIL
            );
        }

        return false;
    }
}
