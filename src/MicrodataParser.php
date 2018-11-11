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
        $result = new \stdClass;

        $items = [];

        foreach ($this->topLevelItems as $topLevelItem) {
            $items[] = $this->getObject($topLevelItem);
        }

        $result->items = $items;

        return $result;
    }

    protected function getObject(\DOMElement $item, $memory = [])
    {
        $result = new \stdClass;

        $memory[] = $item;

        $itemtype = $item->getAttribute('itemtype');
        $result->type = $itemtype ? preg_split('/\s+/', $itemtype) : [];
        // @todo Check if types are valid absolute urls

        if ($itemId = $item->getAttribute('itemid')) {
            $result->id = $itemId;
        }
        // @todo Check if item ids are valid absolute urls or like isbn:xxx

        $properties = new \stdClass;

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

        $result->properties = $properties;

        return $result;
    }

    protected function isItem($element)
    {
        return $element instanceof \DOMElement && $element->hasAttribute('itemscope');
    }
}
