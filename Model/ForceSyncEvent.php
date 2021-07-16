<?php

namespace Oro\Bundle\DotmailerBundle\Model;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * The event can be used to control which entities should be marked for force data fields sync
 */
class ForceSyncEvent extends Event
{
    const NAME = 'oro_dotmailer.on_force_datafields_sync';

    /**
     * @var array
     * [
     *   channelId => [
     *      'entityClass',
     *      'anotherEntityClass'
     *   ]
     * ]
     */
    protected $classes;

    public function __construct(array $classes)
    {
        $this->classes = $classes;
    }

    /**
     * @return array
     */
    public function getClasses()
    {
        return $this->classes;
    }

    public function setClasses(array $classes)
    {
        $this->classes = $classes;
    }
}
