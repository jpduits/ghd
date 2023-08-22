<?php

function strposa(string $haystack, array $needles, int $offset = 0): bool
{
    foreach($needles as $needle) {
        if(strpos($haystack, $needle, $offset) !== false) {
            return true; // stop on first true result
        }
    }

    return false;
}
