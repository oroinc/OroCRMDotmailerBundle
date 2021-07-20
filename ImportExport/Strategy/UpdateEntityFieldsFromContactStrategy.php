<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Strategy;

use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;

class UpdateEntityFieldsFromContactStrategy extends AddOrReplaceStrategy
{
    /**
     * {@inheritdoc}
     */
    protected function beforeProcessEntity($entity)
    {
        //update owner for new entities
        $itemData = $this->context->getValue('itemData');
        if ($itemData && empty($itemData['entityId'])) {
            $channel = $this->getChannel();
            $this->ownerHelper->populateChannelOwner($entity, $channel);
        }

        return ConfigurableAddOrReplaceStrategy::beforeProcessEntity($entity);
    }

    /**
     * {@inheritdoc}
     */
    protected function assertEnvironment($entity)
    {
        ConfigurableAddOrReplaceStrategy::assertEnvironment($entity);
    }

    /**
     * @inheritdoc
     */
    protected function isFieldExcluded($entityName, $fieldName, $itemData = null)
    {
        /**
         * check for identity fields from parent implementation was removed
         * because we need to update the mapped fields only, and identity fields may not be mapped
         */
        $isExcluded = $this->fieldHelper->getConfigValue($entityName, $fieldName, 'excluded', false);
        $isSkipped  = $itemData !== null && !array_key_exists($fieldName, $itemData);

        return $isExcluded || $isSkipped;
    }

    public function setStategyHelper(ImportStrategyHelperWithLog $strategyHelper)
    {
        $this->strategyHelper = $strategyHelper;
    }
}
