<?php

namespace Itx\Utilities;

class DomFetcher
{
    public $xpath = null;

    public function __construct(string $data, $type = null)
    {
        $dom = @new \DOMDocument();
        libxml_use_internal_errors(true);

        $type = $type ?: (
            stripos($data, '<!DOCTYPE html') !== false ? 'html'  : 'xml'
        ) ;

        if ($type == "html") {
            $data = mb_convert_encoding($data, 'HTML-ENTITIES', "UTF-8");
            $data = "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />" . $data;
            $data =  str_replace(array("<body>", "</body>"), array("<div>", "</div>"), $data);
            @$dom->loadHTML($data, LIBXML_NOWARNING);
        } else {
            @$dom->loadXML($data, LIBXML_NOWARNING);
        }

        libxml_clear_errors();
        $this->xpath = @new \DOMXpath($dom);
    }

    public static function using(string $data , $type = null)
    {
        return new self($data , $type);
    }

    public function fetch(array $query)
    {
        $output = [];
        $return = [];

        // //meta[@name!~=i'description']/@content
        /**
         *  !~= not contain and i
         *  !='something' 
         *  !=i'something' ;
         */
        foreach ($query as $key => $q) {
            $as = "text";
            $compine = false;

            if (strpos($q, "asHtml()") != false) {
                $as = "html";
                $q = str_replace("/asHtml()", "", $q);
            }

            if (strpos($q, "join") != false) {
                $as = "text";
                $q = str_replace("/join()", "", $q);
                $compine = true;
            }


            if (strpos($q, "innerHtml()") != false) {
                $q = str_replace("/innerHtml()", "", $q);
                $compine = true;
                $as = "html";
            }

            if (strpos($q, "innerText()") != false) {
                $q = str_replace("/innerText()", "/*", $q);
                $compine = true;
            }


            $q = preg_replace_callback("/(?<what>\@[a-zA-Z0-9\-\_]{1,}|[a-z]{1,}\(\)|[a-zA-Z0-9\-\_]{1,})(?<negate>\!)?(?<selector>[\*|\~\|\^|\!|\$]{1,})\=(?<insensitive>i)?(?<value>[\"']{1}[^'\"].*?[\"']{1})/", function ($output) {

                $sensitive = "%s(%s,%s)";
                $insensitive = "%s(translate( %s ,  'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'),%s)";
                
                if ($output["negate"] == "!") {
                    $sensitive = "not(%s(%s,%s))";
                    $insensitive = "not(%s(translate( %s ,  'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'),%s))";
                }

                $normal =  [
                    "~" => "contains",
                    "^" =>  "starts-with"
                ];

                $custom = [
                    "$" => "substring(:what, string-length(:what) - string-length(:value) +1) " . $output["negate"] . "= :value",
                    "!" => "not(:what and :what = :value)"
                ];

                if (isset($normal[$output["selector"]])) {
                    $template = $output["insensitive"] == "i" ? $insensitive : $sensitive;
                    return sprintf($template, $normal[$output["selector"]], $output["what"], $output["value"]);
                } else {
                    $template = $custom[$output["selector"]];

                    foreach ($output as $key => $value) {
                        $template = str_replace(":" . $key, $value, $template);
                    }

                    return $template;
                }
            }, $q);

            if ($tags = $this->xpath->query($q)) {

                if ($as == "html") {
                    foreach ($tags as $tag) {
                        $output[] = $tag->c14n();
                    }
                } else {
                    foreach ($tags as $tag) {
                        $output[] = $tag->nodeValue;
                    }
                }

                if (count($output) == 1) {
                    $return[$key] = $output[0];
                } else {
                    $output = array_map("trim", $output);
                    $return[$key] = $compine ? implode("\r\n", $output) : $output;
                }
                $output = [];
            }
        }
        return $return;
    }
}
