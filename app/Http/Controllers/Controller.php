<?php

namespace App\Http\Controllers;

use App\Http\ApiResponse;

/**
 * Base Controller
 *
 * All API controllers should extend this class to have access to
 * standardized response methods via the ApiResponse trait.
 */
abstract class Controller
{
    use ApiResponse;
}

