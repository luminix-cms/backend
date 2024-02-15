<?php

namespace Luminix\Backend\Support;

class Traits
{
    static function classUses($class, $trait, $recursive = true)
    {
        return in_array(
            $trait,
            $recursive
                ? class_uses_recursive($class)
                : class_uses($class)
        );
    }
}