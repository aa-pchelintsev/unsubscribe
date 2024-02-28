<?php

require_once "vendor/autoload.php";
require_once "Service/CSV_Functions.php";

use CRM_API\CRM_API;
use Custom_Logger\Custom_Logger;
use CSV_Functions\CSV_Functions;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$url = $_ENV["API_URL"];
$apiKey = $_ENV["API_KEY"];

$api = new CRM_API($url, $apiKey);
$timezone = $api->getTimezone();

$logger = new Custom_Logger();
$csvFunctions = new CSV_Functions($api, $logger);
$csvFunctions->processCSVFile("book1.csv", $timezone);

$logger->logResult->close();
$logger->logError->close();