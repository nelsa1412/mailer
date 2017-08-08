<?php

namespace Acelle\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;

class BlacklistPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
    }
}
