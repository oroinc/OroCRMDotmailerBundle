<?php

namespace Oro\Bundle\DotmailerBundle\Migrations\Schema\v1_8;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Add sync_attempts to orocrm_dm_ab_cnt_export
 */
class AddSyncAttemptsToAbContactsExport implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('orocrm_dm_ab_cnt_export');
        $table->addColumn('sync_attempts', 'smallint', ['notnull' => false, 'unsigned' => true]);
    }
}
