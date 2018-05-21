<?php

namespace Oro\Bundle\DotmailerBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Removing orocrm_dotmailer records from acl_classes
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
