<?php
require_once __DIR__ . '/../readability/Readability.php';
require_once __DIR__ . '/../readability/Configuration.php';
require_once __DIR__ . '/../readability/ParseException.php';
require_once __DIR__ . '/../readability/Nodes/NodeTrait.php';
require_once __DIR__ . '/../readability/Nodes/NodeUtility.php';
require_once __DIR__ . '/../readability/Nodes/DOM/DOMAttr.php';
require_once __DIR__ . '/../readability/Nodes/DOM/DOMCharacterData.php';
require_once __DIR__ . '/../readability/Nodes/DOM/DOMComment.php';
require_once __DIR__ . '/../readability/Nodes/DOM/DOMDocument.php';
require_once __DIR__ . '/../readability/Nodes/DOM/DOMDocumentFragment.php';
require_once __DIR__ . '/../readability/Nodes/DOM/DOMDocumentType.php';
require_once __DIR__ . '/../readability/Nodes/DOM/DOMElement.php';
require_once __DIR__ . '/../readability/Nodes/DOM/DOMEntity.php';
require_once __DIR__ . '/../readability/Nodes/DOM/DOMEntityReference.php';
require_once __DIR__ . '/../readability/Nodes/DOM/DOMNode.php';
require_once __DIR__ . '/../readability/Nodes/DOM/DOMNodeList.php';
require_once __DIR__ . '/../readability/Nodes/DOM/DOMNotation.php';
require_once __DIR__ . '/../readability/Nodes/DOM/DOMProcessingInstruction.php';
require_once __DIR__ . '/../readability/Nodes/DOM/DOMText.php';

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