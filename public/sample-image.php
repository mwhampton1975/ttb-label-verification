<?php

$sampleMap = [
    'bombay_sapphire.jpg' => __DIR__ . '/../samples/bombay_sapphire.jpg',
    'hawks_shadow.jpg' => __DIR__ . '/../samples/hawks_shadow.jpg',
    'stormchaser.jpg' => __DIR__ . '/../samples/stormchaser.jpg',
    'brand-label-new1.jpg' => __DIR__ . '/../samples/brand-label-new1.jpg',
    'brand-label-new2.jpg' => __DIR__ . '/../samples/brand-label-new2.jpg',
    'honey_huckleberry_pie.png' => __DIR__ . '/../samples/honey_huckleberry_pie.png',
    'tropical_chimp.jpg' => __DIR__ . '/../samples/tropical_chimp.jpg',
    'malt_and_hop_india_pale_ale.png' => __DIR__ . '/../samples/malt_and_hop_india_pale_ale.png',
];

$file = $_GET['file'] ?? '';

if ($file === '' || empty($sampleMap[$file]) || !is_file($sampleMap[$file])) {
    http_response_code(404);
    exit('Sample image not found.');
}

$path = $sampleMap[$file];
$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

$contentTypes = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
];

if (empty($contentTypes[$extension])) {
    http_response_code(415);
    exit('Unsupported file type.');
}

header('Content-Type: ' . $contentTypes[$extension]);
header('Content-Length: ' . filesize($path));

readfile($path);
exit;