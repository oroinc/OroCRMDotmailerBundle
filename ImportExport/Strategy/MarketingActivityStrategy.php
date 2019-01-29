<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Strategy;

use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;
use Oro\Bundle\MarketingActivityBundle\Entity\MarketingActivity;

class MarketingActivityStrategy extends AddOrReplaceStrategy
{
    const CACHED_ACTIVITY_TYPE = 'cachedActivityType';

    /** @var  string */
    protected $campaignClassName;

    /**
     * @param string $campaignClassName
     */
    public function setRelatedCampaignClassName($campaignClassName)
    {
        $this->campaignClassName = $campaignClassName;
    }

    /**
     * {@inheritdoc}
     */
    protected function beforeProcessEntity($entity)
    {
        if (!$entity instanceof MarketingActivity) {
            throw new RuntimeException(
                sprintf(
                    'Argument must be an instance of "%s", but "%s" is given',
                    MarketingActivity::class,
                    is_object($entity) ? get_class($entity) : gettype($entity)
                )
            );
        }

        $entity->setRelatedCampaignClass($this->campaignClassName);

        $channel = $this->getChannel();
        $this->ownerHelper->populateChannelOwner($entity, $channel);

        return ConfigurableAddOrReplaceStrategy::beforeProcessEntity($entity);
    }
}
