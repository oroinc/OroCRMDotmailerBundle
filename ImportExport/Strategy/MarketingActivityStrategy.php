<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Strategy;

use Oro\Bundle\DotmailerBundle\Exception\RuntimeException;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;
use Oro\Bundle\MarketingActivityBundle\Entity\MarketingActivity;
use Oro\Bundle\MarketingActivityBundle\Entity\MarketingActivityType;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator\AbstractActivityIterator;

class MarketingActivityStrategy extends AddOrReplaceStrategy
{
    const CACHED_ACTIVITY_TYPE = 'cachedActivityType';

    /** @var  string */
    protected $campaignClassName;

    protected $activityTypes;

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
                    'Oro\Bundle\MarketingActivityBundle\Entity\MarketingActivity',
                    is_object($entity) ? get_class($entity) : gettype($entity)
                )
            );
        }

        $entity->setRelatedCampaignClass($this->campaignClassName);
        $itemData = $this->context->getValue('itemData');
        $activityType = $this->getActivityTypeByName($itemData[AbstractActivityIterator::MARKETING_ACTIVITY_TYPE_KEY]);
        $entity->setType($activityType);

        $channel = $this->getChannel();
        $this->ownerHelper->populateChannelOwner($entity, $channel);

        return ConfigurableAddOrReplaceStrategy::beforeProcessEntity($entity);
    }

    /**
     * @param  string $name
     *
     * @return null|MarketingActivityType
     */
    protected function getActivityTypeByName($name)
    {
        $type = $this->cacheProvider->getCachedItem(self::CACHED_ACTIVITY_TYPE, $name);
        if (!$type) {
            $type = $this->getRepository(MarketingActivityType::class)
                ->findOneBy(['name'  => $name]);

            $this->cacheProvider->setCachedItem(self::CACHED_ACTIVITY_TYPE, $name, $type);
        }

        return $type;
    }
}
