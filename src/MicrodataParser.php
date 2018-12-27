<?php

namespace YusufKandemir\MicrodataParser;

class MicrodataParser
{
    /** @var callable|null */
    protected $absoluteUriHandler;

    /**
     * @param callable|null $absoluteUriHandler
     *
     * @see MicrodataElementParser::$absoluteUriHandler
     */
    public function __construct(callable $absoluteUriHandler = null)
    {
        $this->absoluteUriHandler = $absoluteUriHandler;
    }

    /**
     * Parses HTML string, extracts microdata from it
     *
     * @param string $html HTML string to be parsed
     * @param string $documentURI DocumentURI to be used in absolutizing URIs
     *
     * @return \stdClass
     */
    public function parseHTML(string $html, string $documentURI = '') : \stdClass
    {
        $dom = new \DOMDocument;
        $dom->loadHTML($html, \LIBXML_NOERROR);
        $dom->documentURI = $documentURI;

        return $this->parse($dom);
    }

    /**
     * Parses HTML file, extracts microdata from it
     *
     * @param string $path Path to the file to be parsed
     * @param string $documentURI DocumentURI to be used in absolutizing URIs
     *
     * @return \stdClass
     */
    public function parseHTMLFile(string $path, string $documentURI = '') : \stdClass
    {
        $dom = new \DOMDocument;
        $dom->loadHTMLFile($path, \LIBXML_NOERROR);
        $dom->documentURI = $documentURI;

        return $this->parse($dom);
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
     *
     * @see MicrodataElementParser::$absoluteUriHandler
     *
     * @return \stdClass
     */
    protected function parse(\DOMDocument $dom) : \stdClass
    {
        $elementParser = new MicrodataElementParser($this->absoluteUriHandler);
        $documentParser = new MicrodataDocumentParser($dom, $elementParser);

        return $documentParser->parse();
    }

    /**
     * @param callable|null $absoluteUriHandler
     *
     * @see MicrodataElementParser::$absoluteUriHandler
     *
     * @return MicrodataParser
     */
    public function setAbsoluteUriHandler(callable $absoluteUriHandler = null) : self
    {
        $this->absoluteUriHandler = $absoluteUriHandler;

        return $this;
    }
}
