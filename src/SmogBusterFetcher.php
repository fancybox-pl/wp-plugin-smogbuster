<?php

class SmogBusterFetcher
{
    const STATIONS_URL = 'http://api.gios.gov.pl/pjp-api/rest/station/findAll';
    const AQ_INDEX_URL = 'http://api.gios.gov.pl/pjp-api/rest/aqindex/getIndex/';

    private $wpdb;
    private $updatedAt;
    private $emptyIndexDef;
    private $tableName;

    public function __construct($wpdb)
    {
        $this->wpdb = $wpdb;
        $this->updatedAt = (new \DateTime())->format('Y-m-d H:i:s');
        $this->emptyIndexDef = -1;
        $this->tableName = $this->wpdb->prefix.'smogbuster';
    }

    /**
     * Fetch data from api and persist into database.
     *
     * @return int count of persisted rows
     */
    public function fetch()
    {
        ini_set('max_execution_time', '3600');
        ini_set('memory_limit', '2G');

        $i = 0;
        $stations = $this->curl(self::STATIONS_URL);
        if (is_array($stations)) {
            foreach ($stations as $station) {
                $data = [];
                $data['station_id'] = isset($station['id']) ? $station['id'] : null;
                if (empty($data['station_id'])) {
                    continue;
                }
                $aqIndex = $this->curl(self::AQ_INDEX_URL.$station['id']);

                $data['name'] = isset($station['stationName']) ? $station['stationName'] : null;
                $data['latitude'] = isset($station['gegrLat']) ? $station['gegrLat'] : null;
                $data['longitude'] = isset($station['gegrLon']) ? $station['gegrLon'] : null;
                $data['city'] = isset($station['city']['name']) ? $station['city']['name'] : null;
                $data['address'] = isset($station['addressStreet']) ? $station['addressStreet'] : null;

                $data['st'] = isset($aqIndex['stIndexLevel']['id']) ? $aqIndex['stIndexLevel']['id'] : $this->emptyIndexDef;
                $data['so2'] = isset($aqIndex['so2IndexLevel']['id']) ? $aqIndex['so2IndexLevel']['id'] : $this->emptyIndexDef;
                $data['no2'] = isset($aqIndex['no2IndexLevel']['id']) ? $aqIndex['no2IndexLevel']['id'] : $this->emptyIndexDef;
                $data['co'] = isset($aqIndex['coIndexLevel']['id']) ? $aqIndex['coIndexLevel']['id'] : $this->emptyIndexDef;
                $data['pm10'] = isset($aqIndex['pm10IndexLevel']['id']) ? $aqIndex['pm10IndexLevel']['id'] : $this->emptyIndexDef;
                $data['pm25'] = isset($aqIndex['pm25IndexLevel']['id']) ? $aqIndex['pm25IndexLevel']['id'] : $this->emptyIndexDef;
                $data['o3'] = isset($aqIndex['o3IndexLevel']['id']) ? $aqIndex['o3IndexLevel']['id'] : $this->emptyIndexDef;
                $data['c6h6'] = isset($aqIndex['c6h6IndexLevel']['id']) ? $aqIndex['c6h6IndexLevel']['id'] : $this->emptyIndexDef;
                $data['updated_at'] = $this->updatedAt;

                $this->updateSmogBuster($data);
                ++$i;
            }
        }

        $this->clearUnactualIndexes();

        return $i;
    }

    public function curl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if (200 != $httpCode) {
            throw new \Exception($result);
        }

        return json_decode($result, true);
    }

    public function updateSmogBuster($data)
    {
        $result = $this->wpdb->update($this->tableName, $data, ['station_id' => $data['station_id']]);
        if (!$result) {
            unset($data['updated_at']);
            $data['created_at'] = (new \DateTime())->format('Y-m-d H:i:s');
            $this->wpdb->insert($this->tableName, $data);
        }
    }

    public function clearUnactualIndexes()
    {
        $result = $this->wpdb->get_results("SELECT id FROM $this->tableName WHERE updated_at < '$this->updatedAt'");
        if (is_array($result) && count($result) > 0) {
            $ids = [];
            foreach ($result as $row) {
                $ids[] = $row->id;
            }
            $ids = implode(', ', $ids);

            $data = [
                'st' => $this->emptyIndexDef,
                'so2' => $this->emptyIndexDef,
                'no2' => $this->emptyIndexDef,
                'co' => $this->emptyIndexDef,
                'pm10' => $this->emptyIndexDef,
                'pm25' => $this->emptyIndexDef,
                'o3' => $this->emptyIndexDef,
                'c6h6' => $this->emptyIndexDef,
            ];
            $this->wpdb->update($this->tableName, $data, 'id IN ('.$ids.')');
        }
    }
}
