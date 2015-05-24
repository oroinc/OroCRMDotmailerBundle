<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\DataConverter;

class ContactSyncDataConverter extends AbstractDataConverter
{
    const EMAIL_FIELD = 'email';
    const FIRST_NAME_FIELD = 'firstName';
    const LAST_NAME_FIELD = 'lastName';
    const OPT_IN_TYPE_FIELD = 'optInType';
    const EMAIL_TYPE_FIELD = 'emailType';
    const GENDER_FIELD = 'gender';
    const FULL_NAME_FIELD = 'fullName';
    const POST_CODE_FIELD = 'postcode';

    /**
     * {@inheritdoc}
     */
    protected function getHeaderConversionRules()
    {
        return [
            self::EMAIL_FIELD       => 'email',
            self::FIRST_NAME_FIELD  => 'firstName',
            self::LAST_NAME_FIELD   => 'lastName',
            self::OPT_IN_TYPE_FIELD => 'opt_in_type:id',
            self::EMAIL_TYPE_FIELD  => 'email_type:id',
            self::GENDER_FIELD      => 'gender',
            self::FULL_NAME_FIELD   => 'fullName',
            self::POST_CODE_FIELD   => 'postcode',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getBackendHeader()
    {
        return [];
    }
}
