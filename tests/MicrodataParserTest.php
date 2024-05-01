<?php

namespace YusufKandemir\MicrodataParser\Tests;

use YusufKandemir\MicrodataParser\MicrodataDOMDocument;
use YusufKandemir\MicrodataParser\MicrodataParser;

use function PHPUnit\Framework\assertJsonStringEqualsJsonString;

beforeAll(function () {
    libxml_use_internal_errors(true); // Ignore warnings of DOMDocument::loadHTML check
});

/**
 * @todo Provide more test data
 *
 * @var array{
 *  'W3C Example': array{uri: string, source: string, result: string}[],
 *  'Itemref & src based tags': array{uri: string, source: string, result: string}[],
 *  'Object & Data tags': array{uri: string, source: string, result: string}[],
 *  'Itemid & Content attributes': array{uri: string, source: string, result: string}[]
 * }
 */
$testData = [
    // https://www.w3.org/TR/microdata/#ex-jsonconv
    'W3C Example' => [
        getTestData('W3C', 'source.html', 'result.json'),
    ],
    'Itemref & src based tags' => [
        getTestData('Itemref', 'source.html', 'result.json'),
    ],
    'Object & Data tags' => [
        getTestData('Object & Data', 'source.html', 'result.json'),
    ],
    'Itemid & Content attributes' => [
        getTestData('Itemid & Content', 'source.html', 'result.json'),
    ],
];

it('converts Microdata to object format', function ($data) {
    $parser = getParser($data);

    $result = $parser->toObject();

    expect($result)->toEqual(json_decode($data['result']));
})->with($testData);

it('converts Microdata to array format', function ($data) {
    $parser = getParser($data);

    $result = $parser->toArray();

    expect($result)->toEqual(json_decode($data['result'], true));
})->with($testData);

it('converts Microdata to JSON format', function ($data) {
    $parser = getParser($data);

    $result = $parser->toJSON();

    assertJsonStringEqualsJsonString($data['result'], $result);
})->with($testData);

it('uses absolute URI handler when handling absolute URIs', function () use ($testData) {
    $baseUri = 'https://absolute.uri.handler/';
    $data = $testData['Itemref & src based tags'][0];
    $parser = getParser($data);

    $resultBefore = $parser->toObject();
    $resultBeforeUri = $resultBefore->items[0]->properties->work[0];

    expect($resultBeforeUri)->not->toContain($baseUri);

    $parser->setAbsoluteUriHandler(
        fn (string $value, string $base): string => $baseUri . $value
    );

    $resultAfter = $parser->toObject();
    $resultAfterUri = $resultAfter->items[0]->properties->work[0];

    expect($resultAfterUri)->toContain($baseUri);
});

function getParser($data): MicrodataParser
{
    $dom = new MicrodataDOMDocument();
    $dom->loadHTML($data['source']);
    $dom->documentURI = $data['uri'];

    return new MicrodataParser($dom);
}

/**
 * @return array{uri: string, source: string, result: string}
 */
function getTestData($folderName, $sourceName, $resultName): array
{
    $folderPath = __DIR__ . '/data/' . $folderName . '/';

    $source = file_get_contents($folderPath . $sourceName);
    $result = file_get_contents($folderPath . $resultName);

    if ($source === false || $result === false) {
        throw new \Exception('Could not load test data');
    }

    $uri = '';
    // Set $uri if URI specified in test data
    if (preg_match('/<!-- URI: (.*) -->/', $source, $matches)) {
        $uri = $matches[1];
    }

    return [
        'uri' => $uri,
        'source' => $source, // HTML String
        'result' => $result, // JSON String
    ];
}
