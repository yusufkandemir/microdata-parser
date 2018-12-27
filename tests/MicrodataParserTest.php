<?php

namespace YusufKandemir\MicrodataParser\Tests;

use YusufKandemir\MicrodataParser\MicrodataParser;
use YusufKandemir\MicrodataParser\MicrodataDocumentParser;

class MicrodataParserTest extends DataDrivenTestCase
{
    /**
     * @param array $data
     * @dataProvider data
     */
    public function testItParsesHtml(array $data)
    {
        $parser = new MicrodataParser();
        $result = $parser->parseHTML($data['source'], $data['uri']);

        $expected = \json_decode($data['result']);

        $this->assertEquals($expected, $result);
    }

    /**
     * @param array $data
     * @dataProvider data
     */
    public function testItParsesHtmlFile(array $data)
    {
        $parser = new MicrodataParser();
        $result = $parser->parseHTMLFile($data['path'], $data['uri']);

        $expected = \json_decode($data['result']);

        $this->assertEquals($expected, $result);
    }

    /**
     * @param array $data
     * @dataProvider data
     */
    public function testItParsesDomDocument(array $data)
    {
        $dom = new \DOMDocument;
        $dom->loadHTML($data['source']);
        $dom->documentURI = $data['uri'];

        $parser = new MicrodataParser();
        $result = $parser->parseDOMDocument($dom);

        $expected = \json_decode($data['result']);

        $this->assertEquals($expected, $result);
    }

    public function testItUsesAbsoluteUriHandlerWhenHandlingAbsoluteUris()
    {
        $baseUri = 'https://absolute.uri.handler/';
        $data = $this->data()['Itemref & src based tags'][0];
        $parser = new MicrodataParser();

        $resultBefore = $parser->parseHTML($data['source'], $data['uri']);
        $resultBeforeUri = $resultBefore->items[0]->properties->work[0];

        $this->assertNotContains($baseUri, $resultBeforeUri);

        $absoluteUriHandler = function (string $value, string $base) use ($baseUri) : string {
            return $baseUri . $value;
        };

        $resultAfter = $parser
            ->setAbsoluteUriHandler($absoluteUriHandler)
            ->parseHTML($data['source'], $data['uri']);
        $resultAfterUri = $resultAfter->items[0]->properties->work[0];

        $this->assertContains($baseUri, $resultAfterUri);
    }
}
