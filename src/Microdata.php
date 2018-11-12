<?php

namespace YusufKandemir\MicrodataParser;

abstract class Microdata
{
    public static function fromHTML($html, $documentURI = '')
    {
        $dom = new \DOMDocument;
        $dom->loadHTML($html, LIBXML_NOERROR);
        $dom->documentURI = $documentURI;

        return new MicrodataParser($dom);
    }

    public static function fromHTMLFile($filename, $documentURI = '')
    {
        $dom = new \DOMDocument;
        $dom->loadHTMLFile($filename);
        $dom->documentURI = $documentURI;

        return new MicrodataParser($dom);
    }

    public static function fromDOMDocument(\DOMDocument $dom)
    {
        return new MicrodataParser($dom);
    }
}
