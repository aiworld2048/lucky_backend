<?php

namespace App\Http\Controllers\Api\V1\Game;

use App\Http\Controllers\Api\V1\Game\Buffalo\BuffaloGameController as BuffaloGameControllerAlias;

/**
 * This class exists for backward compatibility. All logic lives in the
 * namespaced Buffalo controller; we simply extend it so existing route
 * bindings keep working.
 */
class BuffaloGameController extends BuffaloGameControllerAlias
{
}

