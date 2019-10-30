<?php

class SmogBusterKernel
{
    const DB_VERSION = '1.0';

    public $fetcher;
    public $api;
    private $wpdb;
    private $tableName;

    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
        $this->fetcher = new SmogBusterFetcher($wpdb);
        $this->api = new SmogBusterApi($wpdb);
        $this->tableName = $this->wpdb->prefix.'smogbuster';
    }

    public function install()
    {
        $sql = '
            CREATE TABLE IF NOT EXISTS `'.$this->tableName.'`
            (
                `id`         INT NOT NULL auto_increment,
                `station_id` INT NULL DEFAULT NULL,
                `name`       VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
                `latitude`   DECIMAL(11, 7) NULL DEFAULT NULL,
                `longitude`  DECIMAL(11, 7) NULL DEFAULT NULL,
                `city`       VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
                `address`    VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL,
                `st`         INT NULL DEFAULT NULL,
                `so2`        INT NULL DEFAULT NULL,
                `no2`        INT NULL DEFAULT NULL,
                `co`         INT NULL DEFAULT NULL,
                `pm10`       INT NULL DEFAULT NULL,
                `pm25`       INT NULL DEFAULT NULL,
                `o3`         INT NULL DEFAULT NULL,
                `c6h6`       INT NULL DEFAULT NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NULL DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) '.$this->wpdb->get_charset_collate().'
        ';
        $this->wpdb->query($sql);

        add_option('smogbuster_db_version', self::DB_VERSION);
    }

    public function uninstall()
    {
        $sql = 'DROP TABLE IF EXISTS `'.$this->tableName.'`';
        $this->wpdb->query($sql);

        delete_option('smogbuster_db_version');
    }
}
