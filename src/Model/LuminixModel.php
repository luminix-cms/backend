<?php

namespace Luminix\Backend\Model;

use Staudenmeir\EloquentEagerLimit\HasEagerLimit;

trait LuminixModel
{

    use GeneratesRoutes, QueryScopes, HasLabel;
    use RelationshipAware, HasEagerLimit, ValidatesRequests;

}
