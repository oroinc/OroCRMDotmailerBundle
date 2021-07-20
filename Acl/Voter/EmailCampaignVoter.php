<?php

namespace Oro\Bundle\DotmailerBundle\Acl\Voter;

use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;

/**
 * Prevents editing of dotmailer email campaigns that are already sent.
 */
class EmailCampaignVoter extends AbstractEntityVoter
{
    /**
     * @var array
     */
    protected $supportedAttributes = [BasicPermission::EDIT];

    /**
     * {@inheritdoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        if ($this->isEmailCampaignSent($identifier)) {
            return self::ACCESS_DENIED;
        }

        return self::ACCESS_ABSTAIN;
    }

    /**
     * @param int $entityId
     * @return bool
     */
    protected function isEmailCampaignSent($entityId)
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
