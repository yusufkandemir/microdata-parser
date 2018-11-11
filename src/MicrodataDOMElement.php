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

        if ($this->hasChildNodes()) {
            $childNodes = iterator_to_array($this->childNodes);

            $childNodes = array_filter($childNodes, function ($node) {
                return $node instanceof \DOMElement;
            }); // Get only DOMElements

            $pending = array_merge($pending, $childNodes);
        }

        if ($this->hasAttribute('itemref')) {
            $tokens = preg_split('/\s+/', $this->getAttribute('itemref'));

            foreach ($tokens as $token) {
                // @todo Implement xpath query and get the first item
            }
        }

        while ($pending) {
            $current = array_pop($pending);

            $error = false;

            foreach ($memory as $memory_item) {
                if ($current->isSameNode($memory_item)) {
                    // There is MicrodataError
                    $error = true;
                    break;
                }
            }

            if ($error) {
                continue;
            }

            $memory[] = $current;

            if (! $current->hasAttribute('itemscope')) {
                if ($current->hasChildNodes()) {
                    $childNodes = iterator_to_array($current->childNodes);

                    $childNodes = array_filter($childNodes, function ($node) {
                        return $node instanceof \DOMElement;
                    });

                    $pending = array_merge($pending, $childNodes);
                }
            }

            if ($current->hasAttribute('itemprop') && /* hasPropertyNames */ $current->getPropertyNames()) {
                $results[] = $current;
            }
        }

        $results = array_reverse($results);

        return $results;
    }

    public function getPropertyNames()
    {
        $itemprop = $this->getAttribute('itemprop');
        $tokens = $itemprop ? preg_split('/\s+/', $itemprop) : [];

        $properties = [];

        foreach ($tokens as $token) {
            if ($this->isAbsoluteUri($token)) {
                $properties[] = $token;
            } elseif ($this->isTypedItem()) {
                $properties[] = /*$vocabularyIdentifier . */ $token;
            } else {
                $properties[] = $token;
            }
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

    public function isTypedItem()
    {
        $tokens = [];

        if ($this->hasAttribute('itemtype')) {
            $tokens = preg_split("/\s+/", $this->getAttribute('itemtype'));
        }

        return !empty($tokens);
    }

    protected function isAbsoluteUri(string $uri)
    {
        return preg_match("/^\w+:/", trim($uri));
    }
}
