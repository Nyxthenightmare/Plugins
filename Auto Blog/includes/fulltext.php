<?php
require_once __DIR__ . '/../readability/Readability.php';
require_once __DIR__ . '/../readability/Configuration.php';
require_once __DIR__ . '/../readability/ParseException.php';

use andreskrey\Readability\Readability;
use andreskrey\Readability\Configuration;

function abft_fetch_fulltext($url) {
    $response = wp_remote_get($url);
    if (is_wp_error($response)) return false;
    $html = wp_remote_retrieve_body($response);

    $config = new Configuration();
    $config->setFixRelativeURLs(true);
    $config->setOriginalURL($url);

    $readability = new Readability($config);
    $readability->parse($html);

    return $readability->getContent();
}