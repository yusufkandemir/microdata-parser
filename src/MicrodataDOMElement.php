<?php

namespace YusufKandemir\MicrodataParser;

use function array_key_exists;
use function in_array;

class MicrodataDOMElement extends \DOMElement
{
    /** @var array "tag name" to "attribute name" mapping */
    private static array $tagNameLookup = [
        'audio' => 'src',
        'embed' => 'src',
        'iframe' => 'src',
        'img' => 'src',
        'source' => 'src',
        'track' => 'src',
        'video' => 'src',
        'a' => 'href',
        'area' => 'href',
        'link' => 'href',
        'object' => 'data',
        'data' => 'value',
        'meter' => 'value',
        'time' => 'datetime',
    ];

    /** @var array Attributes that have absolute values */
    private static array $absoluteAttributes = ['src', 'href', 'data',];

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

        $pending = array_merge($pending, $this->getReferenceNodes());

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
     * @param callable|null $absoluteUriHandler
     *
     * @return $this|string
     */
    public function getPropertyValue(callable $absoluteUriHandler = null): string|static
    {
        if ($this->hasAttribute('itemscope')) {
            return $this;
        }

        if ($this->hasAttribute('content')) {
            return $this->getAttribute('content');
        }

        $value = '';

        if (array_key_exists($this->tagName, self::$tagNameLookup)) {
            $attribute = self::$tagNameLookup[$this->tagName];
            $value = $this->getAttribute($attribute);

            if (!empty($value) && in_array($attribute, self::$absoluteAttributes) && !$this->isAbsoluteUri($value)) {
                $value = $absoluteUriHandler($value, $this->ownerDocument->documentURI);
            }
        }

        return $value ?: $this->textContent;
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
    protected function isAbsoluteUri(string $uri): bool|int
    {
        return preg_match("/^\w+:/", trim($uri));
    }

    /**
     * Filters out TextNodes etc. and returns child ElementNodes as array
     *
     * @return array Result array which contains child ElementNodes
     */
    protected function getChildElementNodes(): array
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
     * @return array|bool
     */
    public function tokenizeAttribute(string $attributeName): array|bool
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
     * @param string $attribute
     *
     * @return array|bool
     * @see \preg_split() for possible return values and behaviour
     *
     * @see https://www.w3.org/TR/2018/WD-microdata-20180426/#dfn-split-a-string-on-spaces for definition of tokens
     *
     */
    protected function tokenize(string $attribute): array|bool
    {
        return preg_split('/\s+/', trim($attribute));
    }

    /**
     * Finds the nodes that this node references through the document
     *
     * @see https://www.w3.org/TR/microdata/#dfn-item-properties 4th step
     *
     * @return array
     */
    protected function getReferenceNodes(): array
    {
        $referenceNodes = [];

        if ($this->hasAttribute('itemref')) {
            $tokens = $this->tokenizeAttribute('itemref');

            foreach ($tokens as $token) {
                $references = $this->ownerDocument->xpath->query('//*[@id="' . $token . '"]');

                if ($first = $references->item(0)) {
                    $referenceNodes[] = $first;
                }
            }
        }

        return $referenceNodes;
    }
}
