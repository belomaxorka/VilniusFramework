<?php

function config(string $key, $default = null): mixed
{
    return \Core\App::config($key, $default);
}
