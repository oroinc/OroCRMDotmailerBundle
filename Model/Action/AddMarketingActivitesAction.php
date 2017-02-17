<?php

namespace Oro\Bundle\DotmailerBundle\Model\Action;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\CampaignBundle\Entity\EmailCampaign;
use Oro\Bundle\DotmailerBundle\Entity\Activity;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\MarketingActivityBundle\Entity\MarketingActivity;
use Oro\Bundle\WorkflowBundle\Model\EntityAwareInterface;

use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\ConfigExpression\ContextAccessor;

class AddMarketingActivitesAction extends AbstractAction
{
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
     * @var EnumValueProvider
     */
    protected $enumProvider;

    public function __construct(
        ContextAccessor $contextAccessor,
        ManagerRegistry $registry,
        EnumValueProvider $enumProvider
    ) {
        parent::__construct($contextAccessor);

        $this->registry = $registry;
        $this->enumProvider = $enumProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function isAllowed($context)
    {
        $isAllowed = false;
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
            if (!$changeSet || isset($changeSet['dateSent'])) {
                $this->addSendActivity($activity, $relatedEntity);
            }
            if ($activity->isUnsubscribed() && (!$changeSet || isset($changeSet['unsubscribed']))) {
                $this->addUnsubscribeActivity($activity, $relatedEntity);
            }
            if ($activity->isSoftBounced() && (!$changeSet || isset($changeSet['softBounced']))) {
                $this->addSoftBounceActivity($activity, $relatedEntity);
            }
            if ($activity->isHardBounced() && (!$changeSet || isset($changeSet['hardBounced']))) {
                $this->addHardBounceActivity($activity, $relatedEntity);
            }
        }

        $this->getEntityManager()->flush();
    }

    /**
     * @param Activity $activity
     * @param array $relatedEntity
     * @return MarketingActivity
     */
    protected function addSendActivity(Activity $activity, $relatedEntity)
    {
        $marketingActivity = $this->prepareMarketingActivity($activity, $relatedEntity);
        //for send activity we know the exact date
        $marketingActivity->setActionDate($activity->getDateSent());
        $marketingActivity->setType($this->getActivityType(MarketingActivity::TYPE_SEND));

        return $marketingActivity;
    }

    /**
     * @param Activity $activity
     * @param array $relatedEntity
     * @return MarketingActivity
     */
    protected function addUnsubscribeActivity(Activity $activity, $relatedEntity)
    {
        $marketingActivity = $this->prepareMarketingActivity($activity, $relatedEntity);
        $marketingActivity->setType($this->getActivityType(MarketingActivity::TYPE_UNSUBSCRIBE));

        return $marketingActivity;
    }

    /**
     * @param Activity $activity
     * @param array $relatedEntity
     * @return MarketingActivity
     */
    protected function addSoftBounceActivity(Activity $activity, $relatedEntity)
    {
        $marketingActivity = $this->prepareMarketingActivity($activity, $relatedEntity);
        $marketingActivity->setType($this->getActivityType(MarketingActivity::TYPE_SOFT_BOUNCE));

        return $marketingActivity;
    }

    /**
     * @param Activity $activity
     * @param array $relatedEntity
     * @return MarketingActivity
     */
    protected function addHardBounceActivity(Activity $activity, $relatedEntity)
    {
        $marketingActivity = $this->prepareMarketingActivity($activity, $relatedEntity);
        $marketingActivity->setType($this->getActivityType(MarketingActivity::TYPE_HARD_BOUNCE));

        return $marketingActivity;
    }

    /**
     * @param Activity $activity
     * @param array $relatedEntity
     * @return MarketingActivity
     */
    protected function prepareMarketingActivity(Activity $activity, $relatedEntity)
    {
        $dmCampaign = $activity->getCampaign();
        $emailCampaign = $dmCampaign->getEmailCampaign();
        $marketingCampaign = $emailCampaign->getCampaign();
        $marketingActivity = new MarketingActivity();
        $marketingActivity->setEntityClass($relatedEntity['entityClass']);
        $marketingActivity->setEntityId($relatedEntity['entityId']);
        $marketingActivity->setCampaign($marketingCampaign);
        $marketingActivity->setRelatedCampaignId($emailCampaign->getId());
        $marketingActivity->setRelatedCampaignClass(EmailCampaign::class);
        $marketingActivity->setActionDate($activity->getUpdatedAt());
        $marketingActivity->setOwner($activity->getOwner());
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

    /**
     * @param $id
     * @return AbstractEnumValue
     */
    protected function getActivityType($id)
    {
        return $this->enumProvider->getEnumValueByCode(MarketingActivity::TYPE_ENUM_CODE, $id);
    }
}
