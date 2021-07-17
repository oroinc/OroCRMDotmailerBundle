<?php

namespace Oro\Bundle\DotmailerBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;
use Oro\Bundle\MarketingListBundle\Model\ContactInformationFieldHelper;
use Oro\Bundle\MarketingListBundle\Provider\ContactInformationFieldsProvider;

class EmailProvider
{
    /** @var ContactInformationFieldHelper */
    protected $contactInformationFieldHelper;

    /** @var VirtualFieldProviderInterface */
    protected $virtualFieldProvider;

    public function __construct(
        ContactInformationFieldHelper $contactInformationFieldHelper,
        VirtualFieldProviderInterface $virtualFieldProvider
    ) {
        $this->contactInformationFieldHelper = $contactInformationFieldHelper;
        $this->virtualFieldProvider = $virtualFieldProvider;
    }

    /**
     * @param string $entityClass
     * @return string
     */
    public function getEntityEmailField($entityClass)
    {
        $contactInformationFields = $this->contactInformationFieldHelper
            ->getEntityContactInformationFieldsInfo($entityClass);
        $emailType = ContactInformationFieldsProvider::CONTACT_INFORMATION_SCOPE_EMAIL;
        $emailField = array_filter(
            $contactInformationFields,
            function ($contactInformationField) use ($emailType) {
                return $contactInformationField['contact_information_type'] === $emailType;
            }
        );
        if (!$emailField) {
            return '';
        }
        $emailField = reset($emailField);
        $emailField = $emailField['name'];

        if ($this->virtualFieldProvider->isVirtualField($entityClass, $emailField)) {
            $fieldConfig = $this->virtualFieldProvider
                ->getVirtualFieldQuery($entityClass, $emailField);
            $select = $fieldConfig['select']['expr'];
            $selectParts = explode('.', $select);
            $fieldConfigJoins = $fieldConfig['join'];
            //if more than 1 field is used in join or select we can't find single source field
            if (count($fieldConfigJoins) > 1 || (count($selectParts) !== 2)) {
                return '';
            }
            $typedFieldConfigJoins = reset($fieldConfigJoins);
            $fieldConfigJoin = reset($typedFieldConfigJoins);
            $joinParts = explode('.', $fieldConfigJoin['join'], 2);
            $emailField = [
                'entityEmailField' => $joinParts[1],
                'emailField' => $selectParts[1]
            ];
        }

        return $emailField;
    }
}
