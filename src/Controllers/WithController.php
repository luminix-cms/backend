<?php

namespace Luminix\Backend\Controllers;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class WithController
{

    public function __construct(
        public string $controller
    ) {
    }

    public function getController(): string
    {
        return $this->controller;
    }
}