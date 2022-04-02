<?php

namespace YusufKandemir\MicrodataParser\Tests;

use DOMDocument;
use YusufKandemir\MicrodataParser\Microdata;
use YusufKandemir\MicrodataParser\MicrodataParser;

class MicrodataTest extends \PHPUnit\Framework\TestCase
{
    protected string $htmlFileName = __DIR__ . '/data/W3C/source.html';

    public function testItCreatesMicrodataParserFromHtml()
    {
        $html = file_get_contents($this->htmlFileName);
        $microdata = Microdata::fromHTML($html);

        $this->assertInstanceOf(MicrodataParser::class, $microdata);
    }

    public function testItCreatesMicrodataParserFromHtmlFile()
    {
        $microdata = Microdata::fromHTMLFile($this->htmlFileName);

        $this->assertInstanceOf(MicrodataParser::class, $microdata);
    }

    public function testItCreatesMicrodataParserFromDomDocument()
    {
        $dom = new DOMDocument();
        $dom->loadHTMLFile($this->htmlFileName);

        $microdata = Microdata::fromDOMDocument($dom);

        $this->assertInstanceOf(MicrodataParser::class, $microdata);
    }
}
