<?php

namespace YusufKandemir\MicrodataParser;

class MicrodataDOMDocument extends \DOMDocument
{
    public $xpath;

    public function getItems()
    {
        return $this->xpath->query('//*[@itemscope and not(@itemprop)]');
    }

    public function loadHTML($source, $options = 0)
    {
        $return = parent::loadHTML($source, $options);

        $this->xpath = new \DOMXPath($this);

        return $return;
    }

    public function loadHTMLFile($filename, $options = 0)
    {
        $return = parent::loadHTMLFile($filename, $options);

        $this->xpath = new \DOMXPath($this);

        return $return;
    }
}
