<?php

namespace YusufKandemir\MicrodataParser;

use DOMDocument;
use DOMXPath;

class MicrodataDOMDocument extends DOMDocument
{
    public DOMXPath $xpath;

    /**
     * Get top-level items of the document.
     *
     * @see https://www.w3.org/TR/2018/WD-microdata-20180426/#dfn-top-level-microdata-item
     *
     * @return \DOMNodeList List of top level items as elements
     */
    public function getItems(): \DOMNodeList
    {
        return $this->xpath->query('//*[@itemscope and not(@itemprop)]');
    }

    /**
     * {@inheritdoc}
     * Also assigns $xpath with DOMXPath of freshly loaded DOMDocument.
     */
    public function loadHTML($source, $options = 0): DOMDocument|bool
    {
        $return = parent::loadHTML($source, $options);

        $this->xpath = new DOMXPath($this);

        return $return;
    }

    /**
     * {@inheritdoc}
     * Also assigns $xpath with DOMXPath of freshly loaded DOMDocument.
     */
    public function loadHTMLFile($filename, $options = 0): DOMDocument|bool
    {
        $return = parent::loadHTMLFile($filename, $options);

        $this->xpath = new DOMXPath($this);

        return $return;
    }
}
