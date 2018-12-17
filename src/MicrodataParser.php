<?php

namespace YusufKandemir\MicrodataParser;

class MicrodataParser
{
    /** @var MicrodataDOMDocument */
    protected $dom;

    /**
     * MicrodataParser constructor.
     *
     * @param MicrodataDOMDocument $dom
     */
    public function __construct(MicrodataDOMDocument $dom)
    {
        $dom->registerNodeClass(\DOMElement::class, MicrodataDOMElement::class);

        $this->dom = $dom;
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
     * @return \stdClass
     */
    public function toObject() : \stdClass
    {
        return $this->extractMicrodata();
    }

    /**
     * Extracts and converts microdata to json using \json_encode()
     *
     * @see \json_encode() to description of parameters and return values
     *
     * @param int $options
     * @param int $depth
     *
     * @return false|string
     */
    public function toJSON($options = 0, $depth = 512)
    {
        return json_encode($this->extractMicrodata(), $options, $depth);
    }

    /**
     * @return \stdClass
     */
    protected function extractMicrodata() : \stdClass
    {
        $result = new \stdClass;

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
     * @return \stdClass
     */
    protected function getObject(MicrodataDOMElement $item, $memory = []) : \stdClass
    {
        $result = new \stdClass;

        $memory[] = $item;

        $result->type = $item->tokenizeAttribute('itemtype');
        // @todo Check if types are valid absolute urls

        if ($item->hasAttribute('itemid')) {
            $result->id = $item->getAttribute('itemid');
        }
        // @todo Check if item ids are valid absolute urls or like isbn:xxx

        $properties = new \stdClass;

        foreach ($item->getProperties() as $element) {
            $value = $element->getPropertyValue();

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
