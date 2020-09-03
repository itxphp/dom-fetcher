
# DomFetcher 

is Xpath on steroids , it makes element selection much easier .

to select an element using class attribute [text case sensitive]

//h1[@class='firstHeading'] ;

for case insensitive

//h1[@class=i'firstheading'] ;

to select using only part of word 

//h1[@class*=i'firstheadi'] ;

to select element starts with part of word

//h1[@class^=i'first'] ;

to select element ends with part of word

//h1[@class$=i'first'] ;

to search for element doesn't contain word

//h1[@class!='firstHeading'] ;




```
<?php
use \Itx\Utilities\DomFetcher ;

$html = file_get_contents('https://en.wikipedia.org/wiki/XPath');

$data = DomFetcher::using($html)->fetch([
    "ogimage" => "//meta[property='og:image']/@content",
    "title" => "//h1[@id=i'firstHeading']" ,
    "description" => "(//div[@class='mw-parser-output']/p)[1] ,
    "references" => "//dive[@class*='reflist']/ol/li" 
]) ;


print_r($data) ;
?>
```