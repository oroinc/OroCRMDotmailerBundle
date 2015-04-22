<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Transport\Iterator;

use DotMailer\Api\Resources\IResources;

class CampaignIterator extends AbstractIterator
{
    /**
     * @var IResources
     */
    protected $dotmailerResources;

    /**
     * @var array ids of address books which related with marketing lists
     */
    protected $addressBooks;

    /**
     * @var int
     */
    protected $indexAddressBook = 0;

    /**
     * {@inheritdoc}
     */
    protected $batchSize = 100;

    /**
     * @param IResources $dotmailerResources
     * @param array      $addressBooks
     */
    public function __construct(IResources $dotmailerResources, $addressBooks)
    {
        $this->dotmailerResources = $dotmailerResources;
        $this->addressBooks = $addressBooks;
    }

    /**
     * {@inheritdoc}
     */
    protected function tryToLoadItems()
    {
        /** Requests count optimization */
        if (!$this->addressBooks || $this->lastPage && ($this->indexAddressBook == count($this->addressBooks))) {
            return false;
        }

        $this->items = $this->getItems($this->batchSize, $this->batchSize * $this->pageNumber);
        if (count($this->items) == 0) {
            return false;
        }

        $this->pageNumber++;
        if (count($this->items) < $this->batchSize) {
            $this->lastPage = true;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function getItems($select, $skip)
    {
        if ($this->lastPage) {
            //next addressBook

            if (empty($this->addressBooks[$this->indexAddressBook])) {
                return [];
            }
            $this->indexAddressBook++;
            $this->isValid = true;
            $this->lastPage = false;
            $this->items = [];
            $this->currentItemIndex = 0;
            $this->pageNumber = 0;

            $items = $this->dotmailerResources->GetAddressBookCampaigns(
                $this->addressBooks[$this->indexAddressBook]['originId'],
                $select,
                0
            );
        } else {
            $items = $this->dotmailerResources->GetAddressBookCampaigns(
                $this->addressBooks[$this->indexAddressBook]['originId'],
                $select,
                $skip
            );
        }

        return $items->toArray();
    }
}
