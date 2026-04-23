<?php
error_reporting(E_ALL);
include('db.class.php');
include('functions.php');

define('MANUSCRIPTS', 600);
#define("MAPPING_URL", "http://localhost:9200/manuscripts");
#define('INDEX_URL', 'http://localhost:9200/manuscripts/_doc');
define("MAPPING_URL", "https://195.169.89.231:9200/manuscripts");
define('INDEX_URL', 'https://195.169.89.231:9200/manuscripts/_doc');
define("MAPPING_FILE", "mapping.json");

indexManuscripts(MANUSCRIPTS);