<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator;

abstract class OverlapIterator extends AbstractIterator
{
    /**
     * @var integer
     */
    protected $overlap = 100;

    /**
     * {@inheritdoc}
     */
    protected function tryToLoadItems($skip = 0)
    {
        $overlap = $this->getOverlapSize();

        /**
         * Overlap necessary because during import some records can be removed or added and it causes shift of records
         * during iteration over them when records are loaded with  batches.
         *
         * At the moment Dotmailer API does not support any filtering or ordering, so overlap is an only workaround
         * solution.
         *
         * @todo Fix in CRM-4627 as soon as Dotmailer API will be updated.
         */
        if ($skip > $overlap) {
            $this->currentItemIndex -= $overlap;
            $skip -= $overlap;
        }

        return parent::tryToLoadItems($skip);
    }

    /**
     * @return int
     */
    public function getOverlapSize()
    {
        return $this->overlap;
    }

    /**
     * @param int $overlap
     */
    public function setOverlapSize($overlap)
    {
        $this->overlap = (int)$overlap;
    }
}
