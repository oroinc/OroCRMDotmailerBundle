<?php

namespace Oro\Bundle\DotmailerBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;

/**
* Origin trait
*
*/
trait OriginTrait
{
    /**
     * Entity origin id
     *
     * @var integer|null
     */
    #[ORM\Column(name: 'origin_id', type: Types::BIGINT, nullable: true)]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true]])]
    protected $originId;

    /**
     * @param int $originId
     *
     * @return $this
     */
    public function setOriginId($originId)
    {
        $this->originId = $originId;

        return $this;
    }

    /**
     * @return int
     */
    public function getOriginId()
    {
        return $this->originId;
    }
}
