<?php

namespace YusufKandemir\MicrodataParser\Tests;

class DataDrivenTestCase extends \PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        libxml_use_internal_errors(true); // Ignore warnings of DOMDocument::loadHTML check
    }

    public function data()
    {
        return [
            // https://www.w3.org/TR/microdata/#ex-jsonconv
            'W3C Example' => [
                $this->getTestData('W3C', 'source.html', 'result.json')
            ],
            'Itemref & src based tags' => [
                $this->getTestData('Itemref', 'source.html', 'result.json')
            ],
            'Object & Data tags' => [
                $this->getTestData('Object & Data', 'source.html', 'result.json')
            ],
            'Itemid & Content attributes' => [
                $this->getTestData('Itemid & Content', 'source.html', 'result.json')
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
            'path' => $folderPath . $sourceName,
            'uri' => $uri,
            'source' => $source, // HTML String
            'result' => $result, // JSON String
        ];
    }
}
