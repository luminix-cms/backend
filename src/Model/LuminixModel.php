<?php

namespace Luminix\Backend\Model;

trait LuminixModel
{

    use HasRestApi, HasResourceScopes, HasIndentifiers;
    use HasRelationHandler, ValidatesRequests;

}
