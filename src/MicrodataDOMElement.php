<?php

namespace YusufKandemir\MicrodataParser;

class MicrodataDOMElement extends \DOMElement
{
    public function getProperties()
    {
        $results = [];
        $memory = [];
        $pending = [];

        $memory[] = $this;

        $pending = array_merge($pending, $this->getChildElementNodes());

        if ($this->hasAttribute('itemref')) {
            $tokens = $this->tokenizeAttribute('itemref');

            foreach ($tokens as $token) {
                // @todo Implement xpath query and get the first item
            }
        }

        while ($pending) {
            $current = array_pop($pending);

            foreach ($memory as $memory_item) {
                if ($current->isSameNode($memory_item)) {
                    continue 2; // Skip next part and continue while loop if memory contains $current
                }
            }

            $memory[] = $current;

            if (! $current->hasAttribute('itemscope')) {
                $pending = array_merge($pending, $current->getChildElementNodes());
            }

            if ($current->hasAttribute('itemprop') && /* hasPropertyNames */ $current->getPropertyNames()) {
                $results[] = $current;
            }
        }

        return array_reverse($results);
    }

    public function getPropertyNames()
    {
        $tokens = $this->tokenizeAttribute('itemprop');

        $properties = [];

        foreach ($tokens as $token) {
            if (!$this->isAbsoluteUri($token) && $this->tokenizeAttribute('itemtype')) {
                $token = /*$vocabularyIdentifier . */ $token;
            }

            $properties[] = $token;
        }

        $properties = array_unique($properties);

        return $properties;
    }

    public function getPropertyValue()
    {

        if ($this->hasAttribute('itemscope')) {
            return $this;
        }

        if ($this->hasAttribute('content')) {
            return $this->getAttribute('content');
        }

        $base = $this->ownerDocument->documentURI;

        switch ($this->tagName) {
            case 'audio':
            case 'embed':
            case 'iframe':
            case 'img':
            case 'source':
            case 'track':
            case 'video':
                if ($this->hasAttribute('src')) {
                    $result = $this->getAttribute('src');

                    // @todo check against protocol relative urls like "//example.com/test.jpg"
                    return $this->isAbsoluteUri($result) ? $result : $base.$result;
                }
            case 'a':
            case 'area':
            case 'link':
                if ($this->hasAttribute('href')) {
                    $result = $this->getAttribute('href');

                    return $this->isAbsoluteUri($result) ? $result : $base.$result;
                }
            case 'object':
                if ($this->hasAttribute('data')) {
                    $result = $this->getAttribute('data');

                    return $this->isAbsoluteUri($result) ? $result : $base.$result;
                }
            case 'data':
            case 'meter':
                if ($this->hasAttribute('value')) {
                    return $this->getAttribute('value');
                }
            case 'time':
                if ($this->hasAttribute('datetime')) {
                    return $this->getAttribute('datetime');
                }
            default:
                return $this->textContent;
        }
    }

    protected function isAbsoluteUri(string $uri)
    {
        return preg_match("/^\w+:/", trim($uri));
    }

    protected function getChildElementNodes()
    {
        $childNodes = [];

        foreach ($this->childNodes as $childNode) {
            if ($childNode->nodeType == XML_ELEMENT_NODE) {
                $childNodes[] = $childNode;
            }
        }

        return $childNodes;
    }

    public function tokenizeAttribute($attributeName) {
        $attribute = [];

        if($this->hasAttribute($attributeName)) {
            $attribute = $this->tokenize($this->getAttribute($attributeName));
        }

        return $attribute;
    }

    protected function tokenize($attribute)
    {
        return preg_split('/\s+/', trim($attribute));
    }
}
