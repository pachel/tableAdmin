<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace pachel;

function error($error) {
    $_ERRORS = [
        0 => "No config loaded",
        1 => "Config file not exists",
        2 => "Key is invalid, ca not save this row",
        3 => "Key is invalid, ca not delete this row",
    ];
    return $_ERRORS[$error];
}
