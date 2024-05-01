<?php

namespace YusufKandemir\MicrodataParser;

class MicrodataDOMDocument extends \DOMDocument
{
    public \DOMXPath $xpath;

    /**
     * Get top-level items of the document.
     *
     * @see https://www.w3.org/TR/2018/WD-microdata-20180426/#dfn-top-level-microdata-item
     *
     * @return \DOMNodeList<MicrodataDOMElement> List of top level items as elements
     */
    public function getItems(): \DOMNodeList
    {
        return $this->xpath->query('//*[@itemscope and not(@itemprop)]');
    }

    /**
     * {@inheritdoc}
     * Also assigns $xpath with DOMXPath of the freshly loaded DOMDocument.
     */
    public function loadHTML($source, $options = 0): bool
    {
        $return = parent::loadHTML($source, $options);

        $this->xpath = new \DOMXPath($this);

        return $return;
    }

    /**
     * {@inheritdoc}
     * Also assigns $xpath with DOMXPath of the freshly loaded DOMDocument.
     */
    public function loadHTMLFile($filename, $options = 0): bool
    {
        $return = parent::loadHTMLFile($filename, $options);

        $this->xpath = new \DOMXPath($this);

        return $return;
    }

    /**
     * Load a DOMDocument instance as the root of this document.
     * Also assigns $xpath with DOMXPath of the freshly loaded DOMDocument.
     * Also copies documentURI from the given DOMDocument.
     */
    public function loadDOMDocument(\DOMDocument $domDocument): void
    {
        $this->documentURI = $domDocument->documentURI;

        $importedNode = $this->importNode($domDocument->documentElement, true);
        $this->appendChild($importedNode);

        $this->xpath = new \DOMXPath($this);
    }
}
