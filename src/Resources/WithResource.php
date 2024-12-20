<?php

namespace Luminix\Backend\Resources;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class WithResource
{
    public function __construct(
        public ?string $single = null,
        public ?string $collection = null,
    ) {}
}