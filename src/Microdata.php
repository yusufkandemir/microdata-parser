<?php

namespace YusufKandemir\MicrodataParser;

abstract class Microdata
{
    public static function fromHTML($html, $documentURI = '')
    {
        $dom = new MicrodataDOMDocument;
        $dom->loadHTML($html, LIBXML_NOERROR);
        $dom->documentURI = $documentURI;

        return new MicrodataParser($dom);
    }

    public static function fromHTMLFile($filename, $documentURI = '')
    {
        $dom = new MicrodataDOMDocument;
        $dom->loadHTMLFile($filename);
        $dom->documentURI = $documentURI;

        return new MicrodataParser($dom);
    }

    public static function fromDOMDocument(\DOMDocument $domDocument)
    {
        $dom = new MicrodataDOMDocument;
        $importedNode = $dom->importNode($domDocument->documentElement, true);
        $dom->appendChild($importedNode);

        return new MicrodataParser($dom);
    }
}
