<?php

namespace Oro\Bundle\DotmailerBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroDotmailerBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateOroIntegrationTransportTable($schema);
    }

    /**
     * Update oro_integration_transport table
     *
     * @param Schema $schema
     */
    protected function updateOroIntegrationTransportTable(Schema $schema)
    {
        $table = $schema->getTable('oro_integration_transport');
        $table->addColumn('orocrm_dm_api_client_id', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('orocrm_dm_api_client_key', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('orocrm_dm_api_custom_domain', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('orocrm_dm_api_refresh_token', 'string', ['notnull' => false, 'length' => 255]);
    }
}
