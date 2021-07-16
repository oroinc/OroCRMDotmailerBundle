<?php

namespace Oro\Bundle\DotmailerBundle\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DotmailerBundle\Provider\ChannelType;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IntegrationSelectType extends AbstractType
{
    const NAME = 'oro_dotmailer_integration_select';
    const ENTITY = 'Oro\Bundle\IntegrationBundle\Entity\Channel';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var AclHelper
     */
    protected $aclHelper;

    public function __construct(ManagerRegistry $registry, AclHelper $aclHelper)
    {
        $this->registry = $registry;
        $this->aclHelper = $aclHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $integrations = $this->getDotmailerIntegrations();
        $options = [
            'class'    => self::ENTITY,
            'choice_label' => 'name',
            'choices'  => $integrations
        ];

        if (count($integrations) != 1) {
            $options['placeholder'] = 'oro.dotmailer.integration.select.placeholder';
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
        return EntityType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
