<?php

namespace YusufKandemir\MicrodataParser;

class MicrodataParser
{
    protected MicrodataDOMDocument $dom;

    /**
     * Handler will be called with $value(non-absolute uri string) and $base(base uri) parameters.
     *
     * Should return a string value
     *
     * @var callable
     */
    private $absoluteUriHandler;

    /**
     * MicrodataParser constructor.
     *
     * @param callable|null $absoluteUriHandler Can be set later with MicrodataParser::setAbsoluteUriHandler()
     *
     * @see MicrodataParser::$absoluteUriHandler
     */
    public function __construct(MicrodataDOMDocument $dom, ?callable $absoluteUriHandler = null)
    {
        $dom->registerNodeClass(\DOMElement::class, MicrodataDOMElement::class);

        $this->dom = $dom;
        $this->absoluteUriHandler = $absoluteUriHandler ?: function ($value, $base) {
            return $base . $value;
        };
    }

    /**
     * Extracts and converts microdata to associative array.
     *
     * @return mixed[]
     *
     * @throws \JsonException
     */
    public function toArray(): array
    {
        // Somewhat hacky way to convert deep objects
        return json_decode(json_encode($this->extractMicrodata(), \JSON_THROW_ON_ERROR), true, flags: \JSON_THROW_ON_ERROR);
    }

    /**
     * Extracts and converts microdata to object.
     */
    public function toObject(): \stdClass
    {
        return $this->extractMicrodata();
    }

    /**
     * Extracts and converts microdata to JSON using \json_encode().
     *
     * @see json_encode() to description of parameters
     *
     * @throws \JsonException
     */
    public function toJSON(int $options = 0, int $depth = 512): string
    {
        return json_encode($this->extractMicrodata(), $options | \JSON_THROW_ON_ERROR, $depth);
    }

    protected function extractMicrodata(): \stdClass
    {
        $result = new \stdClass();

        $result->items = [];

        foreach ($this->dom->getItems() as $item) {
            $result->items[] = $this->getObject($item);
        }

        return $result;
    }

    /**
     * @see https://www.w3.org/TR/2018/WD-microdata-20180426/#dfn-get-the-object
     *
     * @param MicrodataDOMElement[] $memory
     */
    protected function getObject(MicrodataDOMElement $item, array $memory = []): \stdClass
    {
        $result = new \stdClass();

        $memory[] = $item;

        $result->type = $item->tokenizeAttribute('itemtype');
        // @todo Check if types are valid absolute urls

        if ($item->hasAttribute('itemid')) {
            $result->id = $item->getAttribute('itemid');
        }
        // @todo Check if item ids are valid absolute urls or like isbn:xxx

        $properties = new \stdClass();

        foreach ($item->getProperties() as $element) {
            $value = $element->getPropertyValue($this->absoluteUriHandler);

            if ($this->isItem($value)) {
                foreach ($memory as $memoryItem) {
                    if ($element->isSameNode($memoryItem)) {
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
     * Set absolute uri handler.
     */
    public function setAbsoluteUriHandler(callable $handler): void
    {
        $this->absoluteUriHandler = $handler;
    }

    /**
     * Check if the given parameter is a MicrodataDOMElement and has itemscope attribute.
     */
    protected function isItem(mixed $element): bool
    {
        return $element instanceof MicrodataDOMElement && $element->hasAttribute('itemscope');
    }
}
