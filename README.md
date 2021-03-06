[![Build Status](https://travis-ci.org/Barnetik/query-text-parser.png?branch=master)](https://travis-ci.org/Barnetik/query-text-parser)

# Query Text Parser

The Query Text Parser library performs search query text parsing.

This allows you to write a search query in free form text and parse it into a machine-readable parsing tree.

The library is fully unit-tested.

## Features

* AND/OR/NEAR/ADJ operators
* Grouped queries using paranthesis -- i.e. `Denver AND (Boston OR Miami)`
* Operator precedence support: 
 * ADJ|NEAR > AND > OR
 * 'Denver OR Boston AND Miami OR Chicago' equals 'Denver OR (Boston AND Miami) OR Chicago'
* Multi-word search queries using quotes -- i.e. `"San Francisco" AND Chicago`
* Negated matches -- i.e. 'Denver AND -Boston AND -"San Francisco"'

## Example usage

```php
$parser = new \Engage\QueryTextParser\Parser;
$result = $parser->parse('(Chicago AND -Houston) OR Phoenix');
print_r($result);
```

### Output
```
Engage\QueryTextParser\Data\Group Object
(
    [type] => OR
    [children] => Array
        (
            [0] => Engage\QueryTextParser\Data\Group Object
                (
                    [type] => AND
                    [children] => Array
                        (
                            [0] => Engage\QueryTextParser\Data\Partial Object
                                (
                                    [text] => Chicago
                                    [negate] => false
                                )

                            [1] => Engage\QueryTextParser\Data\Partial Object
                                (
                                    [text] => Houston
                                    [negate] => true
                                )

                        )

                )

            [1] => Engage\QueryTextParser\Data\Partial Object
                (
                    [text] => Phoenix
                    [negate] => false
                )

        )

)
```

### Allowing special characters on words
Allowed characters are configurable when on Parser instantiation. Default: ```"\w\*@#\.,\|#~%$&\/\\{\}\*\?\¿_\+\[\]<>"```

```php
// Allow colons inside of words
$parser = new \Engage\QueryTextParser\Parser("\w\*@#\.,\|#~%$&\/\\{\}\*\?\¿_\+\[\]<>:");
$result = $parser->parse('(id:7ab36acd245 AND -Houston) OR Phoenix');
print_r($result);
```
## TODO

* Support NOT operator
