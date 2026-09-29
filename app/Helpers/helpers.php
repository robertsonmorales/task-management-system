<?php

use Mews\Purifier\Facades\Purifier;

function sanitizer($input, $allowed = '') {
    return trim(Purifier::clean($input, [ 'HTML.Allowed' => $allowed ]));
}