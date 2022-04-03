# microdata-parser

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![PHP Version Support][ico-version]]([link-version])
[![Tests][ico-tests]][link-tests]
[![Quality Checks][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]

This package aims to implement [W3C Microdata to JSON Specification](https://www.w3.org/TR/microdata/#json).

**microdata-parser** extracts microdata from documents.

## Installation

Via Composer

``` bash
$ composer require yusufkandemir/microdata-parser
```

## Usage

##### PHP
```php
use YusufKandemir\MicrodataParser\Microdata;

$microdata = Microdata::fromHTMLFile('source.html')->toJSON();
/* Other sources:
     fromHTML()        // from HTML string
     fromDOMDocument() // from DOMDocument object
   Other output methods:
     toArray()  // to Associtive PHP Array
     toObject() // to PHP Object (stdClass)
*/
```

##### Source as HTML
```html
<!-- source.html -->
<div itemscope itemtype="http://schema.org/Product">
  <img itemprop="image" src="http://shop.example.com/test_product.jpg" />
  <a itemprop="url" href="http://shop.example.com/test_product">
    <span itemprop="name">Test Product</span>
  </a>
</div>
```
##### Result as JSON
```json
{
  "items": [
    {
      "type": [ "http://schema.org/Product" ],
      "properties": {
        "image": [ "http://shop.example.com/test_product.jpg" ],
        "url": [ "http://shop.example.com/test_product" ],
        "name": [ "Test Product" ]
      }
    }
  ]
}
```

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Credits

- [Yusuf Kandemir][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/yusufkandemir/microdata-parser.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-version]: https://img.shields.io/packagist/php-v/yusufkandemir/microdata-parser?style=flat-square
[ico-tests]: https://img.shields.io/github/workflow/status/yusufkandemir/microdata-parser/run-tests?style=flat-square&label=tests
[ico-code-quality]: https://img.shields.io/github/workflow/status/yusufkandemir/microdata-parser/analyze-quality?style=flat-square&label=quality
[ico-downloads]: https://img.shields.io/packagist/dt/yusufkandemir/microdata-parser.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/yusufkandemir/microdata-parser
[link-version]: https://packagist.org/packages/yusufkandemir/microdata-parser
[link-tests]: https://github.com/yusufkandemir/microdata-parser/actions/workflows/run-tests.yml
[link-code-quality]: https://github.com/yusufkandemir/microdata-parser/actions/workflows/analyze-quality.yml
[link-downloads]: https://packagist.org/packages/yusufkandemir/microdata-parser
[link-author]: https://github.com/yusufkandemir
[link-contributors]: ../../contributors
