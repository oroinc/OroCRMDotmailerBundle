<?php

namespace Oro\Bundle\DotmailerBundle\ImportExport\Strategy;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper;
use Psr\Log\LoggerInterface;

class ImportStrategyHelperWithLog extends ImportStrategyHelper
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     * Log errors with logger directly. Add item's origin id, if possible, to help identify invalid record
     */
    public function addValidationErrors(array $validationErrors, ContextInterface $context, $errorPrefix = null)
    {
        if (null === $errorPrefix) {
            $itemData = $context->getValue('itemData');
            $errorPrefix = isset($itemData['originId']) ?
                sprintf('Error in row with origin id %s:', $itemData['originId']) :
                sprintf('Error in row %s:', $context->getReadOffset());
        }
        foreach ($validationErrors as $validationError) {
            $this->logger->warning($errorPrefix . ' ' . $validationError);
        }
    }
}
