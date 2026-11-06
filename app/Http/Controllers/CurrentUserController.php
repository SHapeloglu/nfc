<?php

// Oturumdaki (Sanctum ile doğrulanmış) kullanıcıyı JSON olarak döner.
// Kim çağırır: routes/api.php (GET /api/user).

namespace App\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

/**
 * Geçerli kullanıcı denetleyicisi.
 */
class CurrentUserController extends Controller
{
    /**
     * Doğrulanmış kullanıcıyı döner.
     */
    public function __invoke(Request $request): ?Authenticatable
    {
        return $request->user();
    }
}
