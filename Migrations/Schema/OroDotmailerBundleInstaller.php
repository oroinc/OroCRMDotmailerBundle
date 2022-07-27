<?php

namespace Oro\Bundle\DotmailerBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Oro\Bundle\DotmailerBundle\Migration\AddContactExportConnectorToExistedIntegrationsQuery;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OroDotmailerBundleInstaller implements Installation, ExtendExtensionAwareInterface
{
    private ExtendExtension $extendExtension;

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion(): string
    {
        return 'v1_9';
    }

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension): void
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
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
        $this->createOroDotmailerDataFieldTable($schema);
        $this->createOroDotmailerDataFieldMappingTable($schema);
        $this->createOroDotmailerDataFieldMappingConfigTable($schema);
        $this->createOroDotmailerChangeFieldLogTable($schema);
        $this->createOroDotmailerOAuthTable($schema);

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
        $this->addOrocrmDmDataFieldForeignKeys($schema);
        $this->addOrocrmDmDfMappingForeignKeys($schema);
        $this->addOrocrmDmDfMappingConfigForeignKeys($schema);
        $this->addOroDotmailerOAuthForeignKeys($schema);

        $queries->addPostQuery(new AddContactExportConnectorToExistedIntegrationsQuery());
    }

    /**
     * Update oro_integration_transport table
     */
    private function updateOroIntegrationTransportTable(Schema $schema): void
    {
        $table = $schema->getTable('oro_integration_transport');
        $table->addColumn('orocrm_dm_api_username', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('orocrm_dm_api_password', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('orocrm_dm_api_client_id', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('orocrm_dm_api_client_key', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('orocrm_dm_api_custom_domain', 'string', ['notnull' => false, 'length' => 255]);
    }

    /**
     * Create orocrm_dm_campaign table
     */
    private function createOroDotmailerCampaignTable(Schema $schema): void
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
        $table->setPrimaryKey(['id']);
        $table->addIndex(['owner_id'], 'IDX_3D36193A7E3C61F9', []);
        $table->addIndex(['channel_id'], 'IDX_3D36193A72F5A1AA', []);
        $table->addUniqueIndex(['campaign_summary_id'], 'UNIQ_3D36193AEDD5F4F4');
        $table->addUniqueIndex(['origin_id', 'channel_id'], 'orocrm_dm_campaign_unq');
        $table->addUniqueIndex(['email_campaign_id'], 'UNIQ_3D36193AE0F98BC3');

        $this->addEnumField($schema, $table, 'reply_action', 'dm_cmp_reply_action');
        $this->addEnumField($schema, $table, 'status', 'dm_cmp_status');
    }

    /**
     * Create orocrm_dm_address_book table
     */
    private function createOroDotmailerAddressBookTable(Schema $schema): void
    {
        $table = $schema->createTable('orocrm_dm_address_book');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('channel_id', 'integer', ['notnull' => false]);
        $table->addColumn('origin_id', 'bigint', ['notnull' => false]);
        $table->addColumn('marketing_list_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('contact_count', 'integer', ['notnull' => false]);
        $table->addColumn('last_exported_at', 'datetime', ['comment' => '(DC2Type:datetime)', 'notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('last_imported_at', 'datetime', ['comment' => '(DC2Type:datetime)', 'notnull' => false]);
        $table->addColumn('create_entities', 'boolean', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['owner_id'], 'IDX_9A9DD33F7E3C61F9', []);
        $table->addIndex(['channel_id'], 'IDX_9A9DD33F72F5A1AA', []);
        $table->addIndex(['last_imported_at'], 'orocrm_dm_ab_imported_at_idx', []);
        $table->addUniqueIndex(['origin_id', 'channel_id'], 'orocrm_dm_address_book_unq');
        $table->addUniqueIndex(['marketing_list_id'], 'UNIQ_9A9DD33F96434D04');

        $this->addEnumField($schema, $table, 'visibility', 'dm_ab_visibility');
        $this->addEnumField($schema, $table, 'syncStatus', 'dm_import_status');
    }

    /**
     * Create orocrm_dm_contact table
     */
    private function createOroDotmailerContactTable(Schema $schema): void
    {
        $table = $schema->createTable('orocrm_dm_contact');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('channel_id', 'integer', ['notnull' => false]);
        $table->addColumn('origin_id', 'bigint', ['notnull' => false]);
        $table->addColumn('email', 'string', ['length' => 255]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('unsubscribed_date', 'datetime', ['notnull' => false]);
        $table->addColumn('last_subscribed_date', 'datetime', ['notnull' => false]);
        $table->addColumn('data_fields', 'json_array', ['notnull' => false, 'comment' => '(DC2Type:json_array)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['owner_id'], 'IDX_6D7FB88E7E3C61F9', []);
        $table->addIndex(['channel_id'], 'IDX_6D7FB88E72F5A1AA', []);
        $table->addUniqueIndex(['email', 'channel_id'], 'orocrm_dm_cnt_em_unq');
        $table->addUniqueIndex(['origin_id', 'channel_id'], 'orocrm_dm_contact_unq');

        $this->addEnumField($schema, $table, 'opt_in_type', 'dm_cnt_opt_in_type');
        $this->addEnumField($schema, $table, 'email_type', 'dm_cnt_email_type');
        $this->addEnumField($schema, $table, 'status', 'dm_cnt_status');
    }

    /**
     * Create orocrm_dm_activity table
     */
    private function createOroDotmailerActivityTable(Schema $schema): void
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
        $table->setPrimaryKey(['id']);
        $table->addIndex(['channel_id'], 'IDX_8E5702BD72F5A1AA', []);
        $table->addIndex(['owner_id'], 'IDX_8E5702BD7E3C61F9', []);
        $table->addIndex(['campaign_id'], 'IDX_8E5702BDF639F774', []);
        $table->addIndex(['contact_id'], 'IDX_8E5702BDE7A1254A', []);
        $table->addIndex(['email'], 'orocrm_dm_activity_email_idx', []);
        $table->addIndex(['date_sent'], 'orocrm_dm_activity_dt_sent_idx', []);
        $table->addUniqueIndex(['campaign_id', 'contact_id', 'channel_id'], 'orocrm_dm_activity_unq');
    }

    /**
     * Update orocrm_cmpgn_transport_stngs table
     */
    private function updateOroCmpgnTransportStngsTable(Schema $schema): void
    {
        $table = $schema->getTable('orocrm_cmpgn_transport_stngs');
        $table->addColumn('dotmailer_channel_id', 'integer', ['notnull' => false]);
    }

    /**
     * Create orocrm_dm_campaign_summary table
     */
    private function createOroDotmailerCampaignSummaryTable(Schema $schema): void
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
        $table->setPrimaryKey(['id']);
        $table->addIndex(['channel_id'], 'IDX_D7B9893172F5A1AA', []);
        $table->addIndex(['owner_id'], 'IDX_D7B989317E3C61F9', []);
        $table->addIndex(['date_sent'], 'orocrm_dm_camp_sum_dt_sent_idx', []);
        $table->addUniqueIndex(['campaign_id'], 'UNIQ_D7B98931F639F774');
    }

    /**
     * Create orocrm_dm_campaign_to_ab table
     */
    private function createOroDotmailerCampaignToABTable(Schema $schema): void
    {
        $table = $schema->createTable('orocrm_dm_campaign_to_ab');
        $table->addColumn('campaign_id', 'integer', []);
        $table->addColumn('address_book_id', 'integer', []);
        $table->setPrimaryKey(['campaign_id', 'address_book_id']);
        $table->addIndex(['address_book_id'], 'IDX_AA5589424D474419', []);
        $table->addIndex(['campaign_id'], 'IDX_AA558942F639F774', []);
    }

    /**
     * Create orocrm_dm_ab_contact table
     */
    private function createOrocrmDmAbContactTable(Schema $schema): void
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
        $table->addColumn('export_id', 'string', ['notnull' => false, 'length' => 36]);
        $table->addColumn('new_entity', 'boolean', ['notnull' => false]);
        $table->addColumn('entity_updated', 'boolean', ['notnull' => false]);
        $table->addColumn('scheduled_for_fields_update', 'boolean', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['address_book_id'], 'IDX_74DFE8B64D474419', []);
        $table->addIndex(['contact_id'], 'IDX_74DFE8B6E7A1254A', []);
        $table->addIndex(['channel_id'], 'IDX_74DFE8B672F5A1AA', []);
        $table->addIndex(['export_id'], 'orocrm_dm_ab_cnt_export_id_idx', []);
        $table->addIndex(['marketing_list_item_class', 'marketing_list_item_id'], 'IDX_MARKETING_LIST_ITEM_CLASS_ID');
        $table->addUniqueIndex(['address_book_id', 'contact_id'], 'orocrm_dm_ab_cnt_unq');

        $this->addEnumField($schema, $table, 'status', 'dm_cnt_status');
        $this->addEnumField($schema, $table, 'exportOperationType', 'dm_ab_cnt_exp_type');
    }

    /**
     * Create orocrm_dm_ab_cnt_export table
     */
    private function createOrocrmDmAbCntExportTable(Schema $schema): void
    {
        $table = $schema->createTable('orocrm_dm_ab_cnt_export');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('address_book_id', 'integer', ['notnull' => false]);
        $table->addColumn('channel_id', 'integer', ['notnull' => false]);
        $table->addColumn('import_id', 'string', ['length' => 100]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->addColumn('faults_processed', 'boolean', []);
        $table->addColumn('sync_attempts', 'smallint', ['notnull' => false, 'unsigned' => true]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['address_book_id'], 'IDX_5C0830B44D474419', []);
        $table->addIndex(['channel_id'], 'IDX_83CAE83D72F5A1AA', []);
        $table->addIndex(['faults_processed'], 'orocrm_dm_ab_cnt_exp_fault_idx', []);
        $table->addUniqueIndex(['import_id'], 'UNIQ_5C0830B4B6A263D9');

        $this->addEnumField($schema, $table, 'status', 'dm_import_status');
    }

    /**
     * Create orocrm_dm_data_field table
     */
    public function createOroDotmailerDataFieldTable(Schema $schema): void
    {
        $table = $schema->createTable('orocrm_dm_data_field');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('channel_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('default_value', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('notes', 'text', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['owner_id'], 'orocrm_dm_data_field_owner', []);
        $table->addIndex(['channel_id'], 'orocrm_dm_data_field_channel', []);
        $table->addUniqueIndex(['name', 'channel_id'], 'orocrm_dm_data_field_unq');

        $this->addEnumField($schema, $table, 'visibility', 'dm_df_visibility');
        $this->addEnumField($schema, $table, 'type', 'dm_df_type');
    }

    /**
     * Create orocrm_dm_address_book table
     */
    public function createOroDotmailerDataFieldMappingTable(Schema $schema): void
    {
        $table = $schema->createTable('orocrm_dm_df_mapping');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('channel_id', 'integer', ['notnull' => false]);
        $table->addColumn('entity', 'string', ['length' => 255]);
        $table->addColumn('sync_priority', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['owner_id'], 'orocrm_dm_data_field_mapping_owner', []);
        $table->addIndex(['channel_id'], 'orocrm_dm_data_field_mapping_channel', []);
        $table->addUniqueIndex(['entity', 'channel_id'], 'orocrm_dm_data_field_mapping_unq');
    }

    /**
     * Create orocrm_dm_address_book table
     */
    public function createOroDotmailerDataFieldMappingConfigTable(Schema $schema): void
    {
        $table = $schema->createTable('orocrm_dm_df_mapping_config');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('mapping_id', 'integer', ['notnull' => false]);
        $table->addColumn('datafield_id', 'integer', ['notnull' => false]);
        $table->addColumn('entity_field', 'text');
        $table->addColumn('is_two_way_sync', 'boolean', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['mapping_id'], 'orocrm_dm_data_field_mapping_config_mapping', []);
        $table->addIndex(['datafield_id'], 'orocrm_dm_data_field_mapping_config_datafield', []);
        $table->addUniqueIndex(['datafield_id', 'mapping_id'], 'orocrm_dm_df_mapping_config_unq');
    }

    /**
     * Create orocrm_dm_change_field_log table
     */
    private function createOroDotmailerChangeFieldLogTable(Schema $schema): void
    {
        $table = $schema->createTable('orocrm_dm_change_field_log');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('channel_id', 'integer');
        $table->addColumn('parent_entity', 'string', ['length' => 255]);
        $table->addColumn('related_field_path', 'text');
        $table->addColumn('related_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orocrm_dm_oauth table
     */
    private function createOroDotmailerOAuthTable(Schema $schema): void
    {
        $table = $schema->createTable('orocrm_dm_oauth');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('channel_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_id', 'integer', ['notnull' => false]);
        $table->addColumn('refresh_token', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['channel_id'], 'IDX_12771F2872F5A1AA', []);
        $table->addIndex(['user_id'], 'IDX_12771F28A76ED395', []);
        $table->addUniqueIndex(['channel_id', 'user_id'], 'orocrm_dm_oauth_unq');
    }

    /**
     * Add orocrm_dm_campaign foreign keys.
     */
    private function addOroDotmailerCampaignForeignKeys(Schema $schema): void
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
    private function addOroDotmailerAddressBookForeignKeys(Schema $schema): void
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
    private function addOroDotmailerContactForeignKeys(Schema $schema): void
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
    private function addOroDotmailerActivityForeignKeys(Schema $schema): void
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
    private function addOroCmpgnTransportStngsForeignKeys(Schema $schema): void
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
    private function addOroDotmailerCampaignSummaryForeignKeys(Schema $schema): void
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
    private function addOroDotmailerCampaignToABForeignKeys(Schema $schema): void
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
    private function addOrocrmDmAbCntExportForeignKeys(Schema $schema): void
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
    private function addOrocrmDmAbContactForeignKeys(Schema $schema): void
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

    /**
     * Add orocrm_dm_data_field foreign keys.
     */
    private function addOrocrmDmDataFieldForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('orocrm_dm_data_field');
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
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orocrm_dm_df_mapping foreign keys.
     */
    private function addOrocrmDmDfMappingForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('orocrm_dm_df_mapping');
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
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orocrm_dm_df_mapping_config foreign keys.
     */
    private function addOrocrmDmDfMappingConfigForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('orocrm_dm_df_mapping_config');
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_dm_df_mapping'),
            ['mapping_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orocrm_dm_data_field'),
            ['datafield_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orocrm_dm_oauth foreign keys.
     */
    private function addOroDotmailerOAuthForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('orocrm_dm_oauth');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_channel'),
            ['channel_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    private function addEnumField(Schema $schema, Table $table, string $enumField, string $enumCode): void
    {
        $this->extendExtension->addEnumField(
            $schema,
            $table,
            $enumField,
            $enumCode,
            false,
            true,
            ['extend' => ['owner' => ExtendScope::OWNER_CUSTOM] ]
        );
    }
}
