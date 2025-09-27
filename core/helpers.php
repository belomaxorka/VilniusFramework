<?php

function config(string $key, $default = null): mixed
{
    return \Core\Config::get($key, $default);
}
