<?php

namespace Oro\Bundle\DotmailerBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OroDotmailerBundle implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 0;
    }

    /**
     * @SuppressWarnings(PHPMD.ShortMethodName)
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->updateOroIntegrationTransportTable($schema);
        $this->updateOroCmpgnTransportStngsTable($schema);
        $this->createOroDotmailerCampaignTable($schema);
        $this->createOroDotmailerAddressBookTable($schema);
        $this->createOroDotmailerContactTable($schema);
        $this->createOroDotmailerActivityTable($schema);
        $this->createOroDotmailerCampaignSummaryTable($schema);
        $this->createOroDotmailerCampaignToABTable($schema);
        $this->createOrocrmDmAbContactTable($schema);
        $this->createOrocrmDmAbCntExportTable($schema);

        /** Add Foreign Keys */
        $this->addOroCmpgnTransportStngsForeignKeys($schema);
        $this->addOroDotmailerCampaignForeignKeys($schema);
        $this->addOroDotmailerAddressBookForeignKeys($schema);
        $this->addOroDotmailerContactForeignKeys($schema);
        $this->addOroDotmailerActivityForeignKeys($schema);
        $this->addOroDotmailerCampaignSummaryForeignKeys($schema);
        $this->addOroDotmailerCampaignToABForeignKeys($schema);
        $this->addOrocrmDmAbCntExportForeignKeys($schema);
        $this->addOrocrmDmAbContactForeignKeys($schema);
    }

    /**
     * Update oro_integration_transport table
     */
    protected function updateOroIntegrationTransportTable(Schema $schema)
    {
        $table = $schema->getTable('oro_integration_transport');
        $table->addColumn('orocrm_dm_api_username', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('orocrm_dm_api_password', 'string', ['notnull' => false, 'length' => 255]);
    }

    /**
     * Create orocrm_dm_campaign table
     */
    protected function createOroDotmailerCampaignTable(Schema $schema)
    {
        $table = $schema->createTable('orocrm_dm_campaign');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('channel_id', 'integer', ['notnull' => false]);
        $table->addColumn('email_campaign_id', 'integer', ['notnull' => false]);
        $table->addColumn('campaign_summary_id', 'integer', ['notnull' => false]);
        $table->addColumn('origin_id', 'bigint', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('subject', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('from_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('from_address', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('html_content', 'text', ['notnull' => false]);
        $table->addColumn('plain_text_content', 'text', ['notnull' => false]);
        $table->addColumn('reply_to_address', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('is_split_test', 'boolean', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('is_deleted', 'boolean', []);
        $table->addIndex(['owner_id'], 'IDX_3D36193A7E3C61F9', []);
        $table->addIndex(['channel_id'], 'IDX_3D36193A72F5A1AA', []);
        $table->addUniqueIndex(['campaign_summary_id'], 'UNIQ_3D36193AEDD5F4F4');
        $table->addUniqueIndex(['origin_id', 'channel_id'], 'orocrm_dm_campaign_unq');
        $table->addUniqueIndex(['email_campaign_id'], 'UNIQ_3D36193AE0F98BC3');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orocrm_dm_address_book table
     */
    protected function createOroDotmailerAddressBookTable(Schema $schema)
    {
        $table = $schema->createTable('orocrm_dm_address_book');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('channel_id', 'integer', ['notnull' => false]);
        $table->addColumn('origin_id', 'bigint', ['notnull' => false]);
        $table->addColumn('marketing_list_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('contact_count', 'integer', ['notnull' => false]);
        $table->addColumn('last_synced', 'datetime', ['comment' => '(DC2Type:datetime)', 'notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['owner_id'], 'IDX_9A9DD33F7E3C61F9', []);
        $table->addIndex(['channel_id'], 'IDX_9A9DD33F72F5A1AA', []);
        $table->addUniqueIndex(['origin_id', 'channel_id'], 'orocrm_dm_address_book_unq');
        $table->addUniqueIndex(['marketing_list_id'], 'UNIQ_9A9DD33F96434D04');
    }

    /**
     * Create orocrm_dm_contact table
     */
    protected function createOroDotmailerContactTable(Schema $schema)
    {
        $table = $schema->createTable('orocrm_dm_contact');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('channel_id', 'integer', ['notnull' => false]);
        $table->addColumn('origin_id', 'bigint', ['notnull' => false]);
        $table->addColumn('email', 'string', ['length' => 255]);
        $table->addColumn('first_name', 'string', ['notnull' => false, 'length' => 50]);
        $table->addColumn('last_name', 'string', ['notnull' => false, 'length' => 50]);
        $table->addColumn('full_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('gender', 'string', ['notnull' => false, 'length' => 6]);
        $table->addColumn('postcode', 'string', ['notnull' => false, 'length' => 12]);
        $table->addColumn('merge_var_values', 'json_array', ['notnull' => false, 'comment' => '(DC2Type:json_array)']);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('unsubscribed_date', 'datetime', ['notnull' => false]);
        $table->addColumn('last_subscribed_date', 'datetime', ['notnull' => false]);
        $table->addIndex(['owner_id'], 'IDX_6D7FB88E7E3C61F9', []);
        $table->addIndex(['channel_id'], 'IDX_6D7FB88E72F5A1AA', []);
        $table->addUniqueIndex(['email', 'channel_id'], 'orocrm_dm_cnt_em_unq');
        $table->addUniqueIndex(['origin_id', 'channel_id'], 'orocrm_dm_contact_unq');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orocrm_dm_activity table
     */
    protected function createOroDotmailerActivityTable(Schema $schema)
    {
        $table = $schema->createTable('orocrm_dm_activity');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('channel_id', 'integer', ['notnull' => false]);
        $table->addColumn('campaign_id', 'integer', []);
        $table->addColumn('contact_id', 'integer', []);
        $table->addColumn('email', 'string', ['length' => 255]);
        $table->addColumn('num_opens', 'integer', ['notnull' => false]);
        $table->addColumn('num_page_views', 'integer', ['notnull' => false]);
        $table->addColumn('num_clicks', 'integer', ['notnull' => false]);
        $table->addColumn('num_forwards', 'integer', ['notnull' => false]);
        $table->addColumn('num_estimated_forwards', 'integer', ['notnull' => false]);
        $table->addColumn('num_replies', 'integer', ['notnull' => false]);
        $table->addColumn('date_sent', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('date_first_opened', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('date_last_opened', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('first_open_ip', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('unsubscribed', 'boolean', ['notnull' => false]);
        $table->addColumn('soft_bounced', 'boolean', ['notnull' => false]);
        $table->addColumn('hard_bounced', 'boolean', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addIndex(['channel_id'], 'IDX_8E5702BD72F5A1AA', []);
        $table->addIndex(['owner_id'], 'IDX_8E5702BD7E3C61F9', []);
        $table->addIndex(['campaign_id'], 'IDX_8E5702BDF639F774', []);
        $table->addIndex(['contact_id'], 'IDX_8E5702BDE7A1254A', []);
        $table->addUniqueIndex(['campaign_id', 'contact_id', 'channel_id'], 'orocrm_dm_activity_unq');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Update orocrm_cmpgn_transport_stngs table
     */
    protected function updateOroCmpgnTransportStngsTable(Schema $schema)
    {
        $table = $schema->getTable('orocrm_cmpgn_transport_stngs');
        $table->addColumn('dotmailer_channel_id', 'integer', ['notnull' => false]);
    }

    /**
     * Create orocrm_dm_campaign_summary table
     */
    protected function createOroDotmailerCampaignSummaryTable(Schema $schema)
    {
        $table = $schema->createTable('orocrm_dm_campaign_summary');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('channel_id', 'integer', ['notnull' => false]);
        $table->addColumn('campaign_id', 'integer', []);

        $table->addColumn('date_sent', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('num_unique_opens', 'integer', ['notnull' => false]);
        $table->addColumn('num_unique_text_opens', 'integer', ['notnull' => false]);
        $table->addColumn('num_total_unique_opens', 'integer', ['notnull' => false]);
        $table->addColumn('num_opens', 'integer', ['notnull' => false]);
        $table->addColumn('num_text_opens', 'integer', ['notnull' => false]);
        $table->addColumn('num_total_opens', 'integer', ['notnull' => false]);
        $table->addColumn('num_clicks', 'integer', ['notnull' => false]);
        $table->addColumn('num_text_clicks', 'integer', ['notnull' => false]);
        $table->addColumn('num_total_clicks', 'integer', ['notnull' => false]);
        $table->addColumn('num_page_views', 'integer', ['notnull' => false]);
        $table->addColumn('num_total_page_views', 'integer', ['notnull' => false]);
        $table->addColumn('num_text_page_views', 'integer', ['notnull' => false]);
        $table->addColumn('num_forwards', 'integer', ['notnull' => false]);
        $table->addColumn('num_text_forwards', 'integer', ['notnull' => false]);
        $table->addColumn('num_estimated_forwards', 'integer', ['notnull' => false]);
        $table->addColumn('num_text_estimated_forwards', 'integer', ['notnull' => false]);
        $table->addColumn('num_total_estimated_forwards', 'integer', ['notnull' => false]);
        $table->addColumn('num_replies', 'integer', ['notnull' => false]);
        $table->addColumn('num_text_replies', 'integer', ['notnull' => false]);
        $table->addColumn('num_total_replies', 'integer', ['notnull' => false]);
        $table->addColumn('num_hard_bounces', 'integer', ['notnull' => false]);
        $table->addColumn('num_text_hard_bounces', 'integer', ['notnull' => false]);
        $table->addColumn('num_total_hard_bounces', 'integer', ['notnull' => false]);
        $table->addColumn('num_soft_bounces', 'integer', ['notnull' => false]);
        $table->addColumn('num_text_soft_bounces', 'integer', ['notnull' => false]);
        $table->addColumn('num_total_soft_bounces', 'integer', ['notnull' => false]);
        $table->addColumn('num_unsubscribes', 'integer', ['notnull' => false]);
        $table->addColumn('num_text_unsubscribes', 'integer', ['notnull' => false]);
        $table->addColumn('num_total_unsubscribes', 'integer', ['notnull' => false]);
        $table->addColumn('num_isp_complaints', 'integer', ['notnull' => false]);
        $table->addColumn('num_text_isp_complaints', 'integer', ['notnull' => false]);
        $table->addColumn('num_total_isp_complaints', 'integer', ['notnull' => false]);
        $table->addColumn('num_mail_blocks', 'integer', ['notnull' => false]);
        $table->addColumn('num_text_mail_blocks', 'integer', ['notnull' => false]);
        $table->addColumn('num_total_mail_blocks', 'integer', ['notnull' => false]);
        $table->addColumn('num_sent', 'integer', ['notnull' => false]);
        $table->addColumn('num_text_sent', 'integer', ['notnull' => false]);
        $table->addColumn('num_total_sent', 'integer', ['notnull' => false]);
        $table->addColumn('num_recipients_clicked', 'integer', ['notnull' => false]);
        $table->addColumn('num_delivered', 'integer', ['notnull' => false]);
        $table->addColumn('num_text_delivered', 'integer', ['notnull' => false]);
        $table->addColumn('num_total_delivered', 'integer', ['notnull' => false]);
        $table->addColumn('percentage_delivered', 'float', ['notnull' => false]);
        $table->addColumn('percentage_unique_opens', 'float', ['notnull' => false]);
        $table->addColumn('percentage_opens', 'float', ['notnull' => false]);
        $table->addColumn('percentage_unsubscribes', 'float', ['notnull' => false]);
        $table->addColumn('percentage_replies', 'float', ['notnull' => false]);
        $table->addColumn('percentage_hard_bounces', 'float', ['notnull' => false]);
        $table->addColumn('percentage_soft_bounces', 'float', ['notnull' => false]);
        $table->addColumn('percentage_users_clicked', 'float', ['notnull' => false]);
        $table->addColumn('percentage_clicks_to_opens', 'float', ['notnull' => false]);

        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);

        $table->addIndex(['channel_id'], 'IDX_D7B9893172F5A1AA', []);
        $table->addIndex(['owner_id'], 'IDX_D7B989317E3C61F9', []);
        $table->addUniqueIndex(['campaign_id'], 'UNIQ_D7B98931F639F774');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orocrm_dm_campaign_to_ab table
     */
    protected function createOroDotmailerCampaignToABTable(Schema $schema)
    {
        $table = $schema->createTable('orocrm_dm_campaign_to_ab');
        $table->addColumn('campaign_id', 'integer', []);
        $table->addColumn('address_book_id', 'integer', []);
        $table->addIndex(['address_book_id'], 'IDX_AA5589424D474419', []);
        $table->addIndex(['campaign_id'], 'IDX_AA558942F639F774', []);
        $table->setPrimaryKey(['campaign_id', 'address_book_id']);
    }

    /**
     * Create orocrm_dm_ab_contact table
     */
    protected function createOrocrmDmAbContactTable(Schema $schema)
    {
        $table = $schema->createTable('orocrm_dm_ab_contact');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('contact_id', 'integer');
        $table->addColumn('channel_id', 'integer', ['notnull' => false]);
        $table->addColumn('address_book_id', 'integer');
        $table->addColumn('unsubscribed_date', 'datetime', ['notnull' => false]);
        $table->addColumn('marketing_list_item_id', 'integer', ['notnull' => false]);
        $table->addColumn('marketing_list_item_class', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('scheduled_for_export', 'boolean', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['address_book_id', 'contact_id'], 'orocrm_dm_ab_cnt_unq');
        $table->addIndex(['address_book_id'], 'IDX_74DFE8B64D474419', []);
        $table->addIndex(['contact_id'], 'IDX_74DFE8B6E7A1254A', []);
        $table->addIndex(['channel_id'], 'IDX_74DFE8B672F5A1AA', []);
    }

    /**
     * Create orocrm_dm_ab_cnt_export table
     */
    protected function createOrocrmDmAbCntExportTable(Schema $schema)
    {
        $table = $schema->createTable('orocrm_dm_ab_cnt_export');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('address_book_id', 'integer', ['notnull' => false]);
        $table->addColumn('channel_id', 'integer', ['notnull' => false]);
        $table->addColumn('import_id', 'string', ['length' => 100]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['import_id'], 'UNIQ_5C0830B4B6A263D9');
        $table->addIndex(['address_book_id'], 'IDX_5C0830B44D474419', []);
        $table->addIndex(['channel_id'], 'IDX_83CAE83D72F5A1AA', []);
    }

    /**
     * Add orocrm_dm_campaign foreign keys.
     */
    protected function addOroDotmailerCampaignForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orocrm_dm_campaign');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_channel'),
            ['channel_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_campaign_email'),
            ['email_campaign_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_dm_campaign_summary'),
            ['campaign_summary_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * Add orocrm_dm_address_book foreign keys.
     */
    protected function addOroDotmailerAddressBookForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orocrm_dm_address_book');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_channel'),
            ['channel_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_marketing_list'),
            ['marketing_list_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * Add orocrm_dm_contact foreign keys.
     */
    protected function addOroDotmailerContactForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orocrm_dm_contact');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_channel'),
            ['channel_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * Add orocrm_dm_activity foreign keys.
     */
    protected function addOroDotmailerActivityForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orocrm_dm_activity');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_channel'),
            ['channel_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_dm_campaign'),
            ['campaign_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_dm_contact'),
            ['contact_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orocrm_cmpgn_transport_stngs foreign keys.
     */
    protected function addOroCmpgnTransportStngsForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orocrm_cmpgn_transport_stngs');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_channel'),
            ['dotmailer_channel_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * Add orocrm_dm_campaign_summary foreign keys.
     */
    protected function addOroDotmailerCampaignSummaryForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orocrm_dm_campaign_summary');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_channel'),
            ['channel_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_dm_campaign'),
            ['campaign_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orocrm_dm_campaign_to_ab foreign keys.
     */
    protected function addOroDotmailerCampaignToABForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orocrm_dm_campaign_to_ab');
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_dm_address_book'),
            ['address_book_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_dm_campaign'),
            ['campaign_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orocrm_dm_ab_cnt_export foreign keys.
     */
    protected function addOrocrmDmAbCntExportForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orocrm_dm_ab_cnt_export');
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_dm_address_book'),
            ['address_book_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_channel'),
            ['channel_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add orocrm_dm_ab_contact foreign keys.
     */
    protected function addOrocrmDmAbContactForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orocrm_dm_ab_contact');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_channel'),
            ['channel_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_dm_contact'),
            ['contact_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_dm_address_book'),
            ['address_book_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
