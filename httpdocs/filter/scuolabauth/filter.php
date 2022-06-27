<?php

class filter_scuolabauth extends moodle_text_filter
{
    const needle = '{{authorizationtoken}}';

    public function filter($text, array $options = array())
    {
        global $USER;

        if (strpos($text, self::needle) !== false) {
            if (class_exists('local_scuolib_tokenGenerator')) {
                return str_replace(self::needle, local_scuolib_tokenGenerator::generateToken($USER), $text);
            }
        }
        return $text;
    }
}
