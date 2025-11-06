<?php
// admin/includes/functions.php

if (!function_exists('slugify')) {
    function slugify($text) {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = strtolower(trim($text, '-'));
        $text = preg_replace('~-+~', '-', $text);
        if (empty($text)) {
            return 'n-a';
        }
        return $text;
    }
}
?>