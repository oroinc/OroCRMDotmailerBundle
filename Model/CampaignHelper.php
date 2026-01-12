<?php

namespace Oro\Bundle\DotmailerBundle\Model;

/**
 * Helper for campaign-related operations.
 *
 * Provides utility methods for working with Dotmailer campaigns.
 */
class CampaignHelper
{
    /**
     * Generate campaign code based on DM campaign data
     *
     * @param string $name
     * @param string $originId
     *
     * @return string
     */
    public function generateCode($name, $originId)
    {
        $code = preg_replace("/[^a-z0-9]/i", "_", $name);
        $maxNamePart = 20 - (strlen($originId) + 1); //code should be less than 20 symbols
        $code = substr($code, 0, $maxNamePart) . '_' . $originId;

        return $code;
    }
}
