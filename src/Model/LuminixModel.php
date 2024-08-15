<?php

namespace Luminix\Backend\Model;

trait LuminixModel
{

    use HasRestApi, HasResourceScopes, HasIdentifiers;
    use HasRelationHandler, ValidatesRequests;

}
