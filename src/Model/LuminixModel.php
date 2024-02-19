<?php

namespace Luminix\Backend\Model;

use Staudenmeir\EloquentEagerLimit\HasEagerLimit;

trait LuminixModel
{

    use HasRestApi, HasResourceScopes, HasIndentifiers;
    use HasRelationHandler, HasEagerLimit, ValidatesRequests;

}
