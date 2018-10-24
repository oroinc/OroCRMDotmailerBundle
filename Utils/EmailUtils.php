<?php

namespace Oro\Bundle\DotmailerBundle\Utils;

/**
 * This class helps to convert emails to lowercase
 */
class EmailUtils
{
    /**
     * Convert incoming emails to lower case
     *
     * @param array $emails
     * @return array
     */
    public static function getLowerCaseEmails(array $emails)
    {
        foreach ($emails as &$email) {
            $email = self::getLowerCaseEmail($email);
        }

        return $emails;
    }

    /**
     * Convert incoming email to lower case
     *
     * @param string $email
     * @return string
     */
    public static function getLowerCaseEmail($email)
    {
        return strtolower($email);
    }
}
