<?php

namespace OroCRM\Bundle\DotmailerBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCRMDotmailerBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
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
        $table->addColumn('orocrm_dm_api_username', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('orocrm_dm_api_password', 'string', ['notnull' => false, 'length' => 255]);
    }
}
