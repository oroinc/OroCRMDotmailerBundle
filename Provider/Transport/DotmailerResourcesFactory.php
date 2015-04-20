<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Transport;

use DotMailer\Api\Container;
use DotMailer\Api\Resources\IResources;

class DotmailerResourcesFactory
{
    /**
     * @param $username
     * @param $password
     *
     * @return IResources
     * @throws \DotMailer\Api\Exception
     */
    public function createResources($username, $password)
    {
        return Container::newResources([
            Container::USERNAME => $username,
            Container::PASSWORD => $password
        ]);
    }
}
