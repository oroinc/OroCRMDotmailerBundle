<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Transport\Iterator;

abstract class OverlapIterator extends AbstractIterator
{
    /**
     * @var integer
     */
    protected $overlap = 100;

    #[\Override]
    protected function tryToLoadItems($skip = 0)
    {
        $overlap = $this->getOverlapSize();

        /**
         * Overlap necessary because during import some records can be removed or added, and it causes shift of records
         * during iteration over them when records are loaded with batches.
         *
         * At the moment Dotmailer API does not support any filtering or ordering, so overlap is the only workaround
         * solution - see CRM-4627
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
