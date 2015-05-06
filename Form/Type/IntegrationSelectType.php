<?php

namespace OroCRM\Bundle\DotmailerBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

use OroCRM\Bundle\DotmailerBundle\Provider\ChannelType;

class IntegrationSelectType extends AbstractType
{
    const NAME = 'orocrm_dotmailer_integration_select';
    const ENTITY = 'Oro\Bundle\IntegrationBundle\Entity\Channel';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var AclHelper
     */
    protected $aclHelper;

    /**
     * @param ManagerRegistry $registry
     * @param AclHelper $aclHelper
     */
    public function __construct(ManagerRegistry $registry, AclHelper $aclHelper)
    {
        $this->registry = $registry;
        $this->aclHelper = $aclHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $integrations = $this->getDotmailerIntegrations();
        $options = [
            'class'    => self::ENTITY,
            'property' => 'name',
            'choices'  => $integrations
        ];

        if (count($integrations) != 1) {
            $options['empty_value'] = 'orocrm.dotmailer.integration.select.placeholder';
        }

        $resolver->setDefaults($options);
    }

    /**
     * Get dotMailer integration.
     *
     * @return array
     */
    protected function getDotmailerIntegrations()
    {
        $qb = $this->registry->getRepository(self::ENTITY)
            ->createQueryBuilder('c')
            ->andWhere('c.type = :channelType')
            ->andWhere('c.enabled = :enabled')
            ->setParameter('enabled', true)
            ->setParameter('channelType', ChannelType::TYPE)
            ->orderBy('c.name', 'ASC');
        $query = $this->aclHelper->apply($qb);

        return $query->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'entity';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
