<?php

namespace OroCRM\Bundle\DotmailerBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Please, pay attention that this class has incorrect namespace - Oro should be instead of OroCRM.
 * It can cause fatal error if you try to inherit this class.
 */
class RemoveAclCapability implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $queries->addPostQuery("DELETE FROM acl_classes WHERE class_type='orocrm_dotmailer'");
    }
}
