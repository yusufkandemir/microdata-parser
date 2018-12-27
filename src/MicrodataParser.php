<?php

namespace YusufKandemir\MicrodataParser;

class MicrodataParser
{
    /**
     * Parses HTML string, extracts microdata from it
     *
     * @param string $html HTML string to be parsed
     * @param string $documentURI DocumentURI to be used in absolutizing URIs
     * @param callable|null $absoluteUriHandler
     *
     * @see MicrodataElementParser::$absoluteUriHandler
     *
     * @return \stdClass
     */
    public function parseHTML(string $html, string $documentURI = '', callable $absoluteUriHandler = null) : \stdClass
    {
        $dom = new \DOMDocument;
        $dom->loadHTML($html, \LIBXML_NOERROR);
        $dom->documentURI = $documentURI;

        return $this->parse($dom, $absoluteUriHandler);
    }

    /**
     * Parses HTML file, extracts microdata from it
     *
     * @param string $path Path to the file to be parsed
     * @param string $documentURI DocumentURI to be used in absolutizing URIs
     * @param callable|null $absoluteUriHandler
     *
     * @see MicrodataElementParser::$absoluteUriHandler
     *
     * @return \stdClass
     */
    public function parseHTMLFile(string $path, string $documentURI = '', callable $absoluteUriHandler = null) : \stdClass
    {
        $dom = new \DOMDocument;
        $dom->loadHTMLFile($path, \LIBXML_NOERROR);
        $dom->documentURI = $documentURI;

        return $this->parse($dom, $absoluteUriHandler);
    }

    /**
     * Creates a MicrodataParser from a DOMDocument instance.
     * If you have MicrodataDOMDocument then instantiate MicrodataParser class directly to avoid conversion.
     *
     * @param \DOMDocument $domDocument DOMDocument to be parsed.
     * @param string $documentURI If non-empty value is provided,
     *  it will be new value of documentURI property of $domDocument.
     *
     * @return \stdClass
     */
    public function parseDOMDocument(\DOMDocument $domDocument, string $documentURI = '') : \stdClass
    {
        if (!empty($documentURI)) {
            $domDocument->documentURI = $documentURI;
        }

        return $this->parse($domDocument);
    }

    /**
     * @param \DOMDocument $dom
     * @param callable|null $absoluteUriHandler
     *
     * @see MicrodataElementParser::$absoluteUriHandler
     *
     * @return \stdClass
     */
    protected function parse(\DOMDocument $dom, callable $absoluteUriHandler = null) : \stdClass
    {
        $elementParser = new MicrodataElementParser($absoluteUriHandler);
        $documentParser = new MicrodataDocumentParser($dom, $elementParser);

        return $documentParser->parse();
    }
}
