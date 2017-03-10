<?php

namespace Oro\Bundle\DotmailerBundle\Model\Action;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\DotmailerBundle\Entity\Activity;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\MarketingActivityBundle\Entity\MarketingActivity;
use Oro\Bundle\MarketingActivityBundle\Model\ActivityFactory;
use Oro\Bundle\WorkflowBundle\Model\EntityAwareInterface;

use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\ConfigExpression\ContextAccessor;

class AddMarketingActivitesAction extends AbstractAction implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    const OPTION_KEY_CHANGESET = 'changeSet';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var ActivityFactory
     */
    protected $activityFactory;

    public function __construct(
        ContextAccessor $contextAccessor,
        ManagerRegistry $registry,
        ActivityFactory $activityFactory
    ) {
        parent::__construct($contextAccessor);

        $this->registry = $registry;
        $this->activityFactory = $activityFactory;
    }

    /**
     * {@inheritdoc}
     */
    protected function isAllowed($context)
    {
        $isAllowed = $this->isFeaturesEnabled();
        if ($context instanceof EntityAwareInterface) {
            $entity = $context->getEntity();
            if ($entity instanceof Activity) {
                $dmCampaign = $entity->getCampaign();
                $isAllowed = $dmCampaign
                    && $dmCampaign->getEmailCampaign()
                    && $dmCampaign->getEmailCampaign()->getCampaign();
            }
        }

        return $isAllowed && parent::isAllowed($context);
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $changeSet = $this->contextAccessor->getValue($context, $this->options[self::OPTION_KEY_CHANGESET]);
        $this->addActivities($context->getEntity(), $changeSet);
    }

    /**
     * @param Activity $activity
     * @param array|null $changeSet
     */
    protected function addActivities(Activity $activity, $changeSet)
    {
        $dmCampaign = $activity->getCampaign();
        $addressBooks = $dmCampaign->getAddressBooks()->map(
            function (AddressBook $addressBook) {
                return $addressBook->getId();
            }
        )->toArray();
        $relatedEntities = $this->getEntitiesByOriginId($activity->getContact()->getOriginId(), $addressBooks);

        foreach ($relatedEntities as $relatedEntity) {
            $this->processSendActivity($activity, $relatedEntity, $changeSet);
            $this->processUnsubscribeActivity($activity, $relatedEntity, $changeSet);
            $this->processSoftBounceActivity($activity, $relatedEntity, $changeSet);
            $this->processHardBounceActivity($activity, $relatedEntity, $changeSet);
        }
    }

    /**
     * @param Activity $activity
     * @param array $relatedEntity
     * @param array|null $changeSet
     */
    protected function processSendActivity(Activity $activity, $relatedEntity, $changeSet)
    {
        if (!$changeSet || (isset($changeSet['dateSent']) && !$this->datesEqual($changeSet['dateSent']))) {
            $marketingActivity = $this->prepareMarketingActivity(
                $activity,
                MarketingActivity::TYPE_SEND,
                $relatedEntity
            );
            //for send activity we know the exact date
            $marketingActivity->setActionDate($activity->getDateSent());
        }
    }

    /**
     * Compare timestamps of old and new values
     *
     * @param array $dateChangeSet
     * @return bool
     */
    protected function datesEqual($dateChangeSet)
    {
        /** @var \DateTime $oldDate */
        $oldDate = $dateChangeSet['old'];
        /** @var \DateTime $newDate */
        $newDate = $dateChangeSet['new'];
        //add new activity only in case timestamps differ
        return $newDate->getTimestamp() == $oldDate->getTimestamp();
    }

    /**
     * @param Activity $activity
     * @param array $relatedEntity
     * @param array|null $changeSet
     */
    protected function processUnsubscribeActivity(Activity $activity, $relatedEntity, $changeSet)
    {
        if ($activity->isUnsubscribed() && (!$changeSet || isset($changeSet['unsubscribed']))) {
            $this->prepareMarketingActivity(
                $activity,
                MarketingActivity::TYPE_UNSUBSCRIBE,
                $relatedEntity
            );
        }
    }

    /**
     * @param Activity $activity
     * @param array $relatedEntity
     * @param array|null $changeSet
     */
    protected function processSoftBounceActivity(Activity $activity, $relatedEntity, $changeSet)
    {
        if ($activity->isSoftBounced() && (!$changeSet || isset($changeSet['softBounced']))) {
            $this->prepareMarketingActivity(
                $activity,
                MarketingActivity::TYPE_SOFT_BOUNCE,
                $relatedEntity
            );
        }
    }

    /**
     * @param Activity $activity
     * @param array $relatedEntity
     * @param array|null $changeSet
     */
    protected function processHardBounceActivity(Activity $activity, $relatedEntity, $changeSet)
    {
        if ($activity->isHardBounced() && (!$changeSet || isset($changeSet['hardBounced']))) {
            $this->prepareMarketingActivity(
                $activity,
                MarketingActivity::TYPE_HARD_BOUNCE,
                $relatedEntity
            );
        }
    }

    /**
     * @param Activity $activity
     * @param string $type
     * @param array $relatedEntity
     * @return MarketingActivity
     */
    protected function prepareMarketingActivity(Activity $activity, $type, $relatedEntity)
    {
        $dmCampaign = $activity->getCampaign();
        $emailCampaign = $dmCampaign->getEmailCampaign();
        $marketingCampaign = $emailCampaign->getCampaign();
        $marketingActivity = $this->activityFactory->create(
            $marketingCampaign,
            $relatedEntity['entityClass'],
            $relatedEntity['entityId'],
            $activity->getUpdatedAt(),
            $type,
            $activity->getOwner(),
            $emailCampaign->getId()
        );

        $this->getEntityManager()->persist($marketingActivity);

        return $marketingActivity;
    }

    /**
     * @return ObjectManager
     */
    protected function getEntityManager()
    {
        return $this->registry->getManagerForClass(MarketingActivity::class);
    }

    /**
     * @param $originId
     * @param $addressBooks
     * @return BufferedQueryResultIterator
     */
    protected function getEntitiesByOriginId($originId, $addressBooks)
    {
        return $this->registry->getRepository('OroDotmailerBundle:Contact')
            ->getEntitiesDataByOriginIds([$originId], $addressBooks);
    }
}
