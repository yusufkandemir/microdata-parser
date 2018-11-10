<?php

// Microdata Extractor
function extractMicrodata(DOMNodeList $topLevelItems)
{
    // Step 1
    $result = new stdClass;

    // Step 2
    $items = [];

    // Step 3
    // items = map("top-level microdata items", item => getObject(item))
    $items = array_map('getObject', iterator_to_array($topLevelItems));

    /*foreach ($topLevelItems as $topLevelItem) {
        $items[] = getObject($topLevelItem);
    }*/

    // Step 4
    $result->items = $items;

    // Step 5
    return $result;
}


// Get Object

function getObject(DOMElement $item, $memory = [])
{
    // Step 1
    $result = new stdClass();

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
    $properties = new stdClass;

    // Step 7
    foreach (getProperties($item) as $element) {
        $value = getPropertyValue($element);

        if (isItem($value)) {
            foreach ($memory as $memory_item) {
                if ($element->isSameNode($memory_item)) {
                    $value = 'ERROR';
                }
            }

            if ($value != 'ERROR') {
                $value = getObject($value, $memory);
            }
        }

        foreach (getPropertyNames($element) as $name) {
            $properties->{$name}[] = $value;
        }
    }

    // Step 8
    $result->properties = $properties;

    // Step 9
    return $result;
}

// https://www.w3.org/TR/microdata/#dfn-property-name
function getPropertyNames(DOMElement $item)
{
    // Step 1
    $itemprop = $item->getAttribute('itemprop');
    $tokens = $itemprop ? preg_split('/\s+/', $itemprop) : [];

    // Step 2
    $properties = [];

    // Step 3
    foreach ($tokens as $token) {
        if (isAbsoluteUri($token)) {
            $properties[] = $token;
        } elseif (isTypedItem($item)) {
            $properties[] = /*$vocabularyIdentifier . */ $token;
        } else {
            $properties[] = $token;
        }
    }

    $properties = array_unique($properties);

    return $properties;
}

// https://www.w3.org/TR/microdata/#dfn-item-properties
function getProperties(DOMElement $root)
{
    // Step 1
    $results = [];
    $memory = [];
    $pending = [];

    // Step 2
    $memory[] = $root;

    // Step 3
    if ($root->hasChildNodes()) {
        $childNodes = iterator_to_array($root->childNodes);

        $childNodes = array_filter($childNodes, function ($node) {
            return $node instanceof DOMElement;
        }); // Get only DOMElements

        $pending = array_merge($pending, $childNodes);
    }

    // Step 4
    if ($root->hasAttribute('itemref')) {
        $tokens = preg_split('/\s+/', $root->getAttribute('itemref'));

        foreach ($tokens as $token) {
            // @todo Implement xpath query and get the first item
        }
    }

    // Step 5
    while ($pending) {
        // Step 6
        $current = array_pop($pending);

        // Step 7
        // in_array can't compare objects
        /*if (in_array($current, $memory)) {
            // There is MicrodataError
            continue;
        }*/
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

        // Step 8
        $memory[] = $current;

        // Step 9
        if (! $current->hasAttribute('itemscope')) {
            if ($current->hasChildNodes()) {
                $childNodes = iterator_to_array($current->childNodes);

                $childNodes = array_filter($childNodes, function ($node) {
                    return $node instanceof DOMElement;
                });

                $pending = array_merge($pending, $childNodes);
            }
        }

        // Step 10
        if ($current->hasAttribute('itemprop') && /* hasPropertyNames */ getPropertyNames($current)) {
            $results[] = $current;
        }

        // Step 11: Return to loop
    }

    // Step 12: End of loop. Sort results in tree order.

    $results = array_reverse($results);

    // Step 13
    return $results;
}

// https://www.w3.org/TR/microdata/#dfn-property-value
function getPropertyValue(DOMElement $item)
{

    if ($item->hasAttribute('itemscope')) {
        return $item;
    }

    if ($item->hasAttribute('content')) {
        return $item->getAttribute('content');
    }

    $base = $item->ownerDocument->documentURI;

    switch ($item->tagName) {
        case 'audio':
        case 'embed':
        case 'iframe':
        case 'img':
        case 'source':
        case 'track':
        case 'video':
            if ($item->hasAttribute('src')) {
                $result = $item->getAttribute('src');

                // @todo check against protocol relative urls like "//example.com/test.jpg"
                return isAbsoluteUri($result) ? $result : $base.$result;
            }
        case 'a':
        case 'area':
        case 'link':
            if ($item->hasAttribute('href')) {
                $result = $item->getAttribute('href');

                return isAbsoluteUri($result) ? $result : $base.$result;
            }
        case 'object':
            if ($item->hasAttribute('data')) {
                $result = $item->getAttribute('data');

                return isAbsoluteUri($result) ? $result : $base.$result;
            }
        case 'data':
        case 'meter':
            if ($item->hasAttribute('value')) {
                return $item->getAttribute('value');
            }
        case 'time':
            if ($item->hasAttribute('datetime')) {
                return $item->getAttribute('datetime');
            }
        default:
            return $item->textContent;
    }
}

function isItem($element)
{
    return $element instanceof DOMElement && $element->hasAttribute('itemscope');
}

function isTypedItem(DOMElement $item)
{
    $tokens = [];

    if ($item->hasAttribute('itemtype')) {
        $tokens = preg_split("/\s+/", $item->getAttribute('itemtype'));
    }

    return !empty($tokens);
}

function isAbsoluteUri(string $uri)
{
    return preg_match("/^\w+:/", trim($uri));
}
