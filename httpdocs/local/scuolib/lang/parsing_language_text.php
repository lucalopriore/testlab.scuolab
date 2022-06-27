<?php
function parsing_language($text, $languageCode) {
    $substring_multilang_ita = "<span lang=\"".$languageCode."\" class=\"multilang\">";
    $pos_substring = strpos($text,$substring_multilang_ita,0);
    if (is_numeric($pos_substring)) {
        $text = substr($text,$pos_substring+strlen($substring_multilang_ita));
        $pos_substring = strpos($text,"</span>",0);
        $text = substr($text,0,$pos_substring);
    } 
    return $text;
}
?>