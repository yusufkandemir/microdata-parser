<?php

namespace YusufKandemir\MicrodataParser\Tests;

use YusufKandemir\MicrodataParser\MicrodataDOMDocument;
use YusufKandemir\MicrodataParser\MicrodataParser;

class MicrodataParserTest extends DataDrivenTestCase
{
    protected function getParser($data)
    {
        $dom = new MicrodataDOMDocument;
        $dom->loadHTML($data['source']);
        $dom->documentURI = $data['uri'];

        return new MicrodataParser($dom);
    }

    /**
     * @dataProvider data
     */
    public function testItConvertsMicrodataToObjectFormat($data)
    {
        $parser = $this->getParser($data);

        $result = $parser->toObject();

        $this->assertEquals(json_decode($data['result']), $result);
    }

    /**
     * @dataProvider data
     */
    public function testItConvertsMicrodataToArrayFormat($data)
    {
        $parser = $this->getParser($data);

        $result = $parser->toArray();

        $this->assertEquals(json_decode($data['result'], true), $result);
    }

    /**
     * @dataProvider data
     */
    public function testItConvertsMicrodataToJsonFormat($data)
    {
        $parser = $this->getParser($data);

        $result = $parser->toJSON();

        $this->assertJsonStringEqualsJsonString($data['result'], $result);
    }

    public function testItUsesAbsoluteUriHandlerWhenHandlingAbsoluteUris()
    {
        $baseUri = 'https://absolute.uri.handler/';
        $data = $this->data()['Itemref & src based tags'][0];
        $parser = $this->getParser($data);

        $resultBefore = $parser->toObject();
        $resultBeforeUri = $resultBefore->items[0]->properties->work[0];

        $this->assertNotContains($baseUri, $resultBeforeUri);

        $parser->setAbsoluteUriHandler(
            function (string $value, string $base) use ($baseUri) : string {
                return $baseUri . $value;
            }
        );

        $resultAfter = $parser->toObject();
        $resultAfterUri = $resultAfter->items[0]->properties->work[0];

        $this->assertContains($baseUri, $resultAfterUri);
    }
}
