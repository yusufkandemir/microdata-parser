<?php

namespace YusufKandemir\MicrodataParser;

use DOMElement;
use stdClass;

class MicrodataParser
{
    protected MicrodataDOMDocument $dom;

    /**
     * Handler will be called with $value(non-absolute uri string) and $base(base uri) parameters
     *
     * Should return a string value
     *
     * @var callable|null
     */
    private $absoluteUriHandler;

    /**
     * MicrodataParser constructor.
     *
     * @param MicrodataDOMDocument $dom
     * @param callable|null $absoluteUriHandler Can be set later with MicrodataParser::setAbsoluteUriHandler()
     *
     * @see MicrodataParser::$absoluteUriHandler
     */
    public function __construct(MicrodataDOMDocument $dom, callable $absoluteUriHandler = null)
    {
        $dom->registerNodeClass(DOMElement::class, MicrodataDOMElement::class);

        $this->dom = $dom;
        $this->absoluteUriHandler = $absoluteUriHandler ?: function ($value, $base) {
            return $base . $value;
        };
    }

    /**
     * Extracts and converts microdata to associative array
     *
     * @return array
     */
    public function toArray() : array
    {
        // Somewhat hacky way to convert deep objects
        return json_decode(json_encode($this->extractMicrodata()), true);
    }

    /**
     * Extracts and converts microdata to object
     *
     * @return stdClass
     */
    public function toObject() : stdClass
    {
        return $this->extractMicrodata();
    }

    /**
     * Extracts and converts microdata to json using \json_encode()
     *
     * @param int $options
     * @param int $depth
     *
     * @return false|string
     * @see \json_encode() to description of parameters and return values
     *
     */
    public function toJSON(int $options = 0, int $depth = 512): bool|string
    {
        return json_encode($this->extractMicrodata(), $options, $depth);
    }

    /**
     * @return stdClass
     */
    protected function extractMicrodata() : stdClass
    {
        $result = new stdClass;

        $result->items = [];

        foreach ($this->dom->getItems() as $item) {
            $result->items[] = $this->getObject($item);
        }

        return $result;
    }

    /**
     * @see https://www.w3.org/TR/2018/WD-microdata-20180426/#dfn-get-the-object
     *
     * @param MicrodataDOMElement $item
     * @param array $memory
     *
     * @return stdClass
     */
    protected function getObject(MicrodataDOMElement $item, array $memory = []) : stdClass
    {
        $result = new stdClass;

        $memory[] = $item;

        $result->type = $item->tokenizeAttribute('itemtype');
        // @todo Check if types are valid absolute urls

        if ($item->hasAttribute('itemid')) {
            $result->id = $item->getAttribute('itemid');
        }
        // @todo Check if item ids are valid absolute urls or like isbn:xxx

        $properties = new stdClass;

        foreach ($item->getProperties() as $element) {
            $value = $element->getPropertyValue($this->absoluteUriHandler);

            if ($this->isItem($value)) {
                foreach ($memory as $memory_item) {
                    if ($element->isSameNode($memory_item)) {
                        $value = 'ERROR';
                        break;
                    }
                }

                if ($value != 'ERROR') {
                    $value = $this->getObject($value, $memory);
                }
            }

            foreach ($element->getPropertyNames() as $name) {
                $properties->{$name}[] = $value;
            }
        }

        $result->properties = $properties;

        return $result;
    }

    /**
     * Set absolute uri handler
     *
     * @param callable $handler
     */
    public function setAbsoluteUriHandler(callable $handler)
    {
        $this->absoluteUriHandler = $handler;
    }

    /**
     * Check if the given parameter is a MicrodataDOMElement and has itemscope attribute
     *
     * @param $element
     *
     * @return bool
     */
    protected function isItem($element) : bool
    {
        return $element instanceof MicrodataDOMElement && $element->hasAttribute('itemscope');
    }
}
