<?php if ( ! defined( 'ABSPATH' ) ) exit;

class NF_Database_Migrations_Relationships extends NF_Abstracts_Migration
{
    public function __construct()
    {
        parent::__construct(
            'nf3_relationships',
            'nf_migration_create_table_relationships'
        );
    }

    public function run()
    {
        $query = "CREATE TABLE IF NOT EXISTS $this->table_name (
            `id` int NOT NULL AUTO_INCREMENT,
            `child_id` int NOT NULL,
            `child_type` longtext NOT NULL,
            `parent_id` int NOT NULL,
            `parent_type` longtext NOT NULL,
            `created_at` TIMESTAMP,
            `updated_at` DATETIME,
            UNIQUE KEY (`id`)
        ) $this->charset_collate;";

        dbDelta( $query );
    }

}
