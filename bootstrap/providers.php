<?php

use App\Providers\AppServiceProvider;
use App\Providers\RepositoryServiceProvider;
use Laravel\Sanctum\SanctumServiceProvider;

return [
    AppServiceProvider::class,
    RepositoryServiceProvider::class,
    SanctumServiceProvider::class,
];
