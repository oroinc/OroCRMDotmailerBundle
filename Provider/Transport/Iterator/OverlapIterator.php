<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator;

abstract class OverlapIterator extends AbstractIterator
{
    /**
     * @var integer
     */
    protected $overlap;

    /**
     * {@inheritdoc}
     */
    protected function tryToLoadItems($skip = 0)
    {
        $overlap = $this->getOverlapSize();

        /**
         * overlap necessary because of during import some records can be removed or added to records set and
         * Dotmailer API does not support any filtering or ordering, because of it we can miss some entities.
         * This is workaround and it should be removed as soon as Dotmailer API will be updated
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
    abstract public function getOverlapSize();
}
