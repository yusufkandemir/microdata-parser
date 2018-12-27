<?php

namespace YusufKandemir\MicrodataParser;

class MicrodataDocumentParser
{
    /** @var \DOMDocument */
    protected $dom;

    /** @var \DOMXPath */
    protected $xpath;

    /** @var MicrodataElementParser */
    protected $elementParser;

    /**
     * MicrodataParser constructor.
     *
     * @param \DOMDocument $dom DOMDocument to be parsed
     * @param MicrodataElementParser|null $elementParser
     */
    public function __construct(\DOMDocument $dom, MicrodataElementParser $elementParser = null)
    {
        $this->dom = $dom;
        $this->xpath = new \DOMXPath($this->dom);

        $this->elementParser = $elementParser ?? new MicrodataElementParser;
    }

    /**
     * Parses microdata and returns result as object
     *
     * @return \stdClass
     */
    public function parse() : \stdClass
    {
        $result = new \stdClass;

        $result->items = [];

        foreach ($this->getTopLevelItems() as $item) {
            $result->items[] = $this->elementParser->parse($item);
        }

        return $result;
    }

    /**
     * Finds top level items in document
     *
     * @see https://www.w3.org/TR/2018/WD-microdata-20180426/#dfn-top-level-microdata-item
     *
     * @return \DOMNodeList
     */
    protected function getTopLevelItems() : \DOMNodeList
    {
        return $this->xpath->query('//*[@itemscope and not(@itemprop)]');
    }
}
