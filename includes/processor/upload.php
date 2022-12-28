<?php
function process() { 
    global $render;
    if ($render != 'html') {
        http_response_code(415);
        output($contenttype, ['Error'=>"Page can't be rendered for $render"]);
        return false;
    }
    return [];
}