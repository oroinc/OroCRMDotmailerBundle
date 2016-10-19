<?php

namespace Oro\Bundle\DotmailerBundle\Entity;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

trait OriginTrait
{
    /**
     * Entity origin id
     *
     * @var integer
     *
     * @ORM\Column(name="origin_id", type="bigint", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "identity"=true
     *          }
     *      }
     * )
     */
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
