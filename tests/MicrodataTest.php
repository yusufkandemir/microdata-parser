<?php

use YusufKandemir\MicrodataParser\Microdata;
use YusufKandemir\MicrodataParser\MicrodataParser;

$htmlFileName = __DIR__ . '/data/W3C/source.html';

test('it creates MicrodataParser from HTML', function () use ($htmlFileName) {
    $html = file_get_contents($htmlFileName);
    $microdata = Microdata::fromHTML($html);

    expect($microdata)->toBeInstanceOf(MicrodataParser::class);
});

test('it creates MicrodataParser from HTML file', function () use ($htmlFileName) {
    $microdata = Microdata::fromHTMLFile($htmlFileName);

    expect($microdata)->toBeInstanceOf(MicrodataParser::class);
});

test('it creates MicrodataParser from DOMDocument', function () use ($htmlFileName) {
    $dom = new DOMDocument();
    $dom->loadHTMLFile($htmlFileName);

    $microdata = Microdata::fromDOMDocument($dom);

    expect($microdata)->toBeInstanceOf(MicrodataParser::class);
});
