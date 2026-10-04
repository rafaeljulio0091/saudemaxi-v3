<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesTenantResources;

abstract class TenantResourcePolicy
{
    use AuthorizesTenantResources;
}
