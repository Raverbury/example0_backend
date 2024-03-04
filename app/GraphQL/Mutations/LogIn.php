<?php declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Models\User;

final readonly class LogIn
{
    /** @param  array{}  $args */
    public function __invoke(null $_, array $args)
    {
        return User::attemptLogIn($args);
    }
}
