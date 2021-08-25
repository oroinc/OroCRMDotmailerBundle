<?php

namespace Oro\Bundle\DotmailerBundle\Acl\Voter;

use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;

/**
 * Prevents editing of dotmailer email campaigns that are already sent.
 */
class EmailCampaignVoter extends AbstractEntityVoter
{
    /** {@inheritDoc} */
    protected $supportedAttributes = [BasicPermission::EDIT];

    /**
     * {@inheritDoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        if ($this->isEmailCampaignSent($identifier)) {
            return self::ACCESS_DENIED;
        }

        return self::ACCESS_ABSTAIN;
    }

    private function isEmailCampaignSent(int $entityId): bool
    {
        $emailCampaign = $this->doctrineHelper
            ->getEntityRepository($this->className)
            ->find($entityId);

        if ($emailCampaign) {
            return $emailCampaign->isSent();
        }

        return false;
    }
}
