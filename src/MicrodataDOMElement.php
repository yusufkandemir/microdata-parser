<?php

namespace YusufKandemir\MicrodataParser;

class MicrodataDOMElement extends \DOMElement
{
    /**
     * @see https://www.w3.org/TR/2018/WD-microdata-20180426/#dfn-item-properties for details of algorithm
     *
     * @return array
     */
    public function getProperties() : array
    {
        $results = [];
        $memory = [$this];
        $pending = $this->getChildElementNodes();

        if ($this->hasAttribute('itemref')) {
            $tokens = $this->tokenizeAttribute('itemref');

            foreach ($tokens as $token) {
                $references = $this->ownerDocument->xpath->query('//*[@id="'.$token.'"]');

                if ($first = $references->item(0)) {
                    $pending[] = $first;
                }
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

            if ($current->hasAttribute('itemprop') && $current->hasPropertyNames()) {
                $results[] = $current;
            }
        }

        return array_reverse($results);
    }

    /**
     * @return bool
     */
    public function hasPropertyNames() : bool
    {
        return !empty($this->tokenizeAttribute('itemprop'));
    }

    /**
     * @see https://www.w3.org/TR/2018/WD-microdata-20180426/#dfn-property-name
     *
     * @return array
     */
    public function getPropertyNames() : array
    {
        $tokens = $this->tokenizeAttribute('itemprop');

        $properties = [];

        foreach ($tokens as $token) {
            if (!$this->isAbsoluteUri($token) && $this->tokenizeAttribute('itemtype')) {
                $token = /*$vocabularyIdentifier . */ $token;
            }

            $properties[] = $token;
        }

        return array_unique($properties);
    }

    /**
     * @see https://www.w3.org/TR/2018/WD-microdata-20180426/#dfn-property-value for details of algorithm
     *
     * @return $this|string
     */
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
                // No break
            case 'a':
            case 'area':
            case 'link':
                if ($this->hasAttribute('href')) {
                    $result = $this->getAttribute('href');

                    return $this->isAbsoluteUri($result) ? $result : $base.$result;
                }
                // No break
            case 'object':
                if ($this->hasAttribute('data')) {
                    $result = $this->getAttribute('data');

                    return $this->isAbsoluteUri($result) ? $result : $base.$result;
                }
                // No break
            case 'data':
            case 'meter':
                if ($this->hasAttribute('value')) {
                    return $this->getAttribute('value');
                }
                // No break
            case 'time':
                if ($this->hasAttribute('datetime')) {
                    return $this->getAttribute('datetime');
                }
                // No break
            default:
                return $this->textContent;
        }
    }

    /**
     * Checks a string to see if its absolute uri or not
     * Note: As it uses a simple regex to check, it is not that reliable
     *
     * @see \preg_match() for return values
     *
     * @param string $uri
     *
     * @return false|int
     */
    protected function isAbsoluteUri(string $uri)
    {
        return preg_match("/^\w+:/", trim($uri));
    }

    /**
     * Filters out TextNodes etc. and returns child ElementNodes as array
     *
     * @return array Result array which contains child ElementNodes
     */
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

    /**
     * Tokenizes value of given attribute
     *
     * @param string $attributeName Name of the attribute
     *
     * @return array|array[]|false|string[]
     */
    public function tokenizeAttribute(string $attributeName)
    {
        $attribute = [];

        if ($this->hasAttribute($attributeName)) {
            $attribute = $this->tokenize($this->getAttribute($attributeName));
        }

        return $attribute;
    }

    /**
     * Splits given attribute value in space characters to array
     *
     * @see \preg_split() for possible return values and behaviour
     *
     * @see https://www.w3.org/TR/2018/WD-microdata-20180426/#dfn-split-a-string-on-spaces for definition of tokens
     *
     * @param string $attribute
     *
     * @return array[]|false|string[]
     */
    protected function tokenize(string $attribute)
    {
        return preg_split('/\s+/', trim($attribute));
    }
}
