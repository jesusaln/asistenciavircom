<?php

namespace App\Helpers;

class SanitizerHelper
{
    /**
     * Purify HTML to prevent XSS while allowing safe tags.
     * If no tags are allowed, it acts as a more secure version of strip_tags.
     */
    public static function clean(mixed $content): string
    {
        if (!is_string($content)) {
            return (string) $content;
        }

        // 1. Remove any null bytes
        $content = str_replace(chr(0), '', $content);

        // 2. More aggressive stripping if we want NO HTML
        $content = strip_tags($content);

        // 3. Convert special characters to HTML entities to prevent XSS on output
        // Note: Usually we do this on output, but if we want to store "safe" text,
        // we can do it here or just ensure it's done in the views.
        // However, many parts of this app use v-html or {!! !!}, so 
        // storing them as entities is safer for this specific codebase.
        
        return htmlspecialchars($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Clean and trim a string
     */
    public static function cleanTrim(?string $content): string
    {
        return trim(self::clean($content ?? ''));
    }
}
