<?php
error_reporting(E_ALL);
include('db.class.php');
include('functions.php');

define('MANUSCRIPTS', 500);
define("MAPPING_URL", "http://localhost:9200/manuscripts");
define('INDEX_URL', 'http://localhost:9200/manuscripts/_doc');
//define("INDEX_PLACES_URL", 'http://localhost:9200/sont/port');
define("MAPPING_FILE", "mapping.json");

indexManuscripts(MANUSCRIPTS);