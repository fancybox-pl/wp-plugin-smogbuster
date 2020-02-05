# SmogBuster WP plugin

For synchronization air quality data from **api.gios.gov.pl**

Api documentation [http://powietrze.gios.gov.pl/pjp/content/api](http://powietrze.gios.gov.pl/pjp/content/api)

## Instalation process
1. Install plugin
1. Data will be sync hourly by wp cron

## Synchronization air quality data

Url for manually sync
```
https://yourdomain.com/wp-json/smogbuster/sync
```

## Getting air quality data from database
Url for get data from database

* response: JSON
* metohd: GET

```
https://yourdomain.com/wp-json/smogbuster/stations
```

Example response:

```javascript
[
  {
    "id": 1,
    "station_id": 114,
    "name": "WrocĹaw - Bartnicza",
    "latitude": "51.1159330",
    "longitude": "17.1411250",
    "city": "WrocĹaw",
    "address": "ul. Bartnicza",
    "st": 0,
    "so2": -1,
    "no2": 0,
    "co": -1,
    "pm10": -1,
    "pm25": -1,
    "o3": 0,
    "c6h6": -1,
    "created_at": "2019-01-08 15:33:33",
    "updated_at": "2019-01-09 08:31:02"
  },
  ...
]
```

## Shortcode for map

``[shortcode]``
