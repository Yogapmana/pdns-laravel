<?php

declare(strict_types=1);

namespace App\Http\Controllers;

/**
 * Base controller class for all HTTP controllers in the application.
 *
 * Concrete controllers (Admin, Guru, Siswa namespaces) extend this class
 * to inherit shared behaviour and to provide a single common ancestor
 * for type-hinting and service-container binding.
 */
abstract class Controller
{
    //
}
