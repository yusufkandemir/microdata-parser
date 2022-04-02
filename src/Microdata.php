<?php

namespace YusufKandemir\MicrodataParser;

abstract class Microdata
{
    /**
     * Creates a MicrodataParser from HTML string.
     *
     * @param string $html        HTML string to be parsed
     * @param string $documentURI DocumentURI to be used in absolutizing URIs
     */
    public static function fromHTML(string $html, string $documentURI = ''): MicrodataParser
    {
        $dom = new MicrodataDOMDocument();
        $dom->loadHTML($html, \LIBXML_NOERROR);
        $dom->documentURI = $documentURI;

        return new MicrodataParser($dom);
    }

    /**
     * Creates a MicrodataParser from a HTML file.
     *
     * @param string $filename    Path to the file to be parsed
     * @param string $documentURI DocumentURI to be used in absolutizing URIs
     */
    public static function fromHTMLFile(string $filename, string $documentURI = ''): MicrodataParser
    {
        $dom = new MicrodataDOMDocument();
        $dom->loadHTMLFile($filename, \LIBXML_NOERROR);
        $dom->documentURI = $documentURI;

        return new MicrodataParser($dom);
    }

    /**
     * Creates a MicrodataParser from a DOMDocument instance.
     * If you have MicrodataDOMDocument then instantiate MicrodataParser class directly to avoid conversion.
     *
     * @param \DOMDocument $domDocument DOMDocument to be parsed.
     *                                  Needs to have documentURI property to be used in absolutizing URIs if wanted.
     */
    public static function fromDOMDocument(\DOMDocument $domDocument): MicrodataParser
    {
        $dom = new MicrodataDOMDocument();
        $importedNode = $dom->importNode($domDocument->documentElement, true);
        $dom->appendChild($importedNode);

        return new MicrodataParser($dom);
    }
}
