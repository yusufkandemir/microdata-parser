<?php

namespace YusufKandemir\MicrodataParser;

class MicrodataElementParser
{
    /** @var array "tag name" to "attribute name" mapping */
    private static $tagNameLookup = [
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
    private static $absoluteAttributes = ['src', 'href', 'data',];

    /**
     * Handler will be called with $value(non-absolute uri string) and $base(base uri) parameters
     *
     * Should return the processed uri (string)
     *
     * @var callable|null
     */
    private $absoluteUriHandler;

    /**
     * @param callable|null $absoluteUriHandler Can be set later with setAbsoluteUriHandler()
     */
    public function __construct(callable $absoluteUriHandler = null)
    {
        $this->absoluteUriHandler = $absoluteUriHandler ?: function ($value, $base) {
            return $base . $value;
        };
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
     * @see https://www.w3.org/TR/2018/WD-microdata-20180426/#dfn-get-the-object
     *
     * @param \DOMElement $item
     * @param array $memory
     *
     * @return \stdClass
     */
    public function parse(\DOMElement $item, $memory = []) : \stdClass
    {
        $result = new \stdClass;

        $memory[] = $item;

        $result->type = $this->tokenizeAttribute($item, 'itemtype');
        // @todo Check if types are valid absolute urls

        if ($item->hasAttribute('itemid')) {
            $result->id = $item->getAttribute('itemid');
        }
        // @todo Check if item ids are valid absolute urls or like isbn:xxx

        $properties = new \stdClass;

        foreach ($this->getProperties($item) as $element) {
            $value = $this->getPropertyValue($element, $this->absoluteUriHandler);

            if ($this->isItem($value)) {
                foreach ($memory as $memory_item) {
                    if ($element->isSameNode($memory_item)) {
                        $value = 'ERROR';
                        break;
                    }
                }

                if ($value != 'ERROR') {
                    $value = $this->parse($value, $memory);
                }
            }

            foreach ($this->getPropertyNames($element) as $name) {
                $properties->{$name}[] = $value;
            }
        }

        $result->properties = $properties;

        return $result;
    }

    /**
     * @see https://www.w3.org/TR/2018/WD-microdata-20180426/#dfn-item-properties for details of algorithm
     *
     * @param \DOMElement $element
     *
     * @return array
     */
    protected function getProperties(\DOMElement $element) : array
    {
        $results = [];
        $memory = [$element];
        $pending = $this->getChildElementNodes($element);

        $pending = array_merge($pending, $this->getReferenceElements($element));

        while ($pending) {
            $current = array_pop($pending);

            foreach ($memory as $memory_item) {
                if ($current->isSameNode($memory_item)) {
                    continue 2; // Skip next part and continue while loop if memory contains $current
                }
            }

            $memory[] = $current;

            if (! $current->hasAttribute('itemscope')) {
                $pending = array_merge($pending, $this->getChildElementNodes($current));
            }

            if ($current->hasAttribute('itemprop') && $this->hasPropertyNames($current)) {
                $results[] = $current;
            }
        }

        return array_reverse($results);
    }

    /**
     * @param \DOMElement $element
     *
     * @return bool
     */
    public function hasPropertyNames(\DOMElement $element) : bool
    {
        return !empty($this->tokenizeAttribute($element, 'itemprop'));
    }

    /**
     * @see https://www.w3.org/TR/2018/WD-microdata-20180426/#dfn-property-name
     *
     * @param \DOMElement $element
     *
     * @return array
     */
    public function getPropertyNames(\DOMElement $element) : array
    {
        $tokens = $this->tokenizeAttribute($element, 'itemprop');

        $properties = [];

        foreach ($tokens as $token) {
            if (!$this->isAbsoluteUri($token) && $this->tokenizeAttribute($element, 'itemtype')) {
                $token = /*$vocabularyIdentifier . */ $token;
            }

            $properties[] = $token;
        }

        return \array_unique($properties);
    }

    /**
     * @see https://www.w3.org/TR/2018/WD-microdata-20180426/#dfn-property-value for details of algorithm
     *
     * @param \DOMElement $element
     * @param callable $absoluteUriHandler
     *
     * @return \DOMElement|string
     */
    public function getPropertyValue(\DOMElement $element, callable $absoluteUriHandler = null)
    {
        if ($element->hasAttribute('itemscope')) {
            return $element;
        }

        if ($element->hasAttribute('content')) {
            return $element->getAttribute('content');
        }

        $value = '';

        if (\array_key_exists($element->tagName, self::$tagNameLookup)) {
            $attribute = self::$tagNameLookup[$element->tagName];
            $value = $element->getAttribute($attribute);

            if (!empty($value) && \in_array($attribute, self::$absoluteAttributes) && !$this->isAbsoluteUri($value)) {
                $value = $absoluteUriHandler($value, $element->ownerDocument->documentURI);
            }
        }

        return $value ?: $element->textContent;
    }

    /**
     * Finds the elements that given element references through the document
     *
     * @see https://www.w3.org/TR/microdata/#dfn-item-properties 4th step
     *
     * @param \DOMElement $element
     *
     * @return array
     */
    protected function getReferenceElements(\DOMElement $element): array
    {
        $referenceElements = [];

        if ($element->hasAttribute('itemref')) {
            $tokens = $this->tokenizeAttribute($element, 'itemref');

            foreach ($tokens as $token) {
                $referenceElement = $element->ownerDocument->getElementById($token);

                if ($referenceElement instanceof \DOMElement) {
                    $referenceElements[] = $referenceElement;
                }
            }
        }

        return $referenceElements;
    }

    /**
     * Filters out TextNodes etc. and returns child ElementNodes as array
     *
     * @param \DOMElement $element
     *
     * @return array Result array which contains child ElementNodes
     */
    protected function getChildElementNodes(\DOMElement $element)
    {
        $childNodes = [];

        foreach ($element->childNodes as $childNode) {
            if ($childNode->nodeType == XML_ELEMENT_NODE) {
                $childNodes[] = $childNode;
            }
        }

        return $childNodes;
    }

    /**
     * Tokenizes value of given attribute
     *
     * @param \DOMElement $element
     * @param string $attributeName Name of the attribute
     *
     * @return array|array[]|false|string[]
     */
    public function tokenizeAttribute(\DOMElement $element, string $attributeName)
    {
        $attribute = [];

        if ($element->hasAttribute($attributeName)) {
            $attribute = $this->tokenize($element->getAttribute($attributeName));
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
     * Check if the given parameter is a DOMElement and has itemscope attribute
     *
     * @param $element
     *
     * @return bool
     */
    protected function isItem($element) : bool
    {
        return $element instanceof \DOMElement && $element->hasAttribute('itemscope');
    }
}
