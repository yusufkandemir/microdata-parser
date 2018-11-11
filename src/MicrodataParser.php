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

        $result->items = [];

        foreach ($this->topLevelItems as $topLevelItem) {
            $result->items[] = $this->getObject($topLevelItem);
        }

        return $result;
    }

    protected function getObject(\DOMElement $item, $memory = [])
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

    protected function isItem($element)
    {
        return $element instanceof \DOMElement && $element->hasAttribute('itemscope');
    }
}
