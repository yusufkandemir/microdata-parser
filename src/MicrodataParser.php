<?php

namespace YusufKandemir\MicrodataParser;

class MicrodataParser
{
    protected $topLevelItems;

    public function __construct(\DOMDocument $dom)
    {
        $dom->registerNodeClass(\DOMElement::class, MicrodataDOMElement::class);

        $xpath = new \DOMXPath($dom);
        $this->topLevelItems = $xpath->query('//*[@itemscope and not(@itemprop)]');
    }

    public function extractMicrodata()
    {
        // Step 1
        $result = new \stdClass;

        // Step 2
        $items = [];

        // Step 3
        foreach ($this->topLevelItems as $topLevelItem) {
            $items[] = $this->getObject($topLevelItem);
        }

        // Step 4
        $result->items = $items;

        // Step 5
        return $result;
    }

    protected function getObject(\DOMElement $item, $memory = [])
    {
        // Step 1
        $result = new \stdClass;

        // Step 2 in 2nd parameter of this function
        // $memory = [];

        // Step 3
        $memory[] = $item;

        // Step 4
        $itemtype = $item->getAttribute('itemtype');
        $result->type = $itemtype ? preg_split('/\s+/', $itemtype) : [];
        // @todo Check if types are valid absolute urls

        // Step 5
        if ($itemId = $item->getAttribute('itemid')) {
            $result->id = $itemId;
        }
        // @todo Check if item ids are valid absolute urls or like isbn:xxx

        // Step 6
        $properties = new \stdClass;

        // Step 7
        foreach ($item->getProperties() as $element) {
            $value = $element->getPropertyValue();

            if ($this->isItem($value)) {
                foreach ($memory as $memory_item) {
                    if ($element->isSameNode($memory_item)) {
                        $value = 'ERROR';
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

        // Step 8
        $result->properties = $properties;

        // Step 9
        return $result;
    }

    protected function isItem($element)
    {
        return $element instanceof \DOMElement && $element->hasAttribute('itemscope');
    }
}
