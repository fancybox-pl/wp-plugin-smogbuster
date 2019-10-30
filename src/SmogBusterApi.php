<?php

class SmogBusterApi
{
    private $wpdb;
    private $tableName;

    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
        $this->tableName = $this->wpdb->prefix.'smogbuster';
    }

    public function getAirQuality()
    {
        $result = $this->wpdb->get_results("SELECT * FROM $this->tableName", ARRAY_A);

        return $result;
    }
}
