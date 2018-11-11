<?php

namespace YusufKandemir\MicrodataParser\Tests;

use YusufKandemir\MicrodataParser\MicrodataParser;

class MicrodataParserTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        libxml_use_internal_errors(true); // Ignore warnings of DOMDocument::loadHTML check
    }

    /**
     * @dataProvider data
     */
    public function testExtractData($data)
    {
        $dom = new \DOMDocument;
        $dom->loadHTML($data['source']);
        $dom->documentURI = $data['uri'];

        $parser = new MicrodataParser($dom);

        $result = $parser->extractMicrodata();

        $this->assertEquals($data['result'], $result);
    }

    /**
     * @todo Provide more test data
     */
    public function data()
    {
        return [
            // https://www.w3.org/TR/microdata/#ex-jsonconv
            'W3C Example' => [
                $this->getTestData('W3C', 'source.html', 'result.json')
            ],
        ];
    }

    private function getTestData($folderName, $sourceName, $resultName)
    {
        $folderPath = __DIR__.'/data/'.$folderName.'/';

        $source = file_get_contents($folderPath . $sourceName);
        $result = file_get_contents($folderPath . $resultName);

        $uri = '';
        // Set $uri if URI specified in test data
        if (preg_match('/<!-- URI: (.*) -->/', $source, $matches)) {
            $uri = $matches[1];
        }

        return [
            'uri' => $uri,
            'source' => $source,
            'result' => json_decode($result),
        ];
    }
}
