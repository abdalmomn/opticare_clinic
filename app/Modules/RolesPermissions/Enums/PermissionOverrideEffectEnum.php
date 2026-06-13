<?php

namespace App\Modules\RolesPermissions\Enums;

use App\Modules\Core\Enums\Concerns\HasEnumValues;

/**
 * Effect of a per-staff permission override.
 *
 * grant = add the permission to this staff member.
 * deny  = block the permission for this staff member even if a role grants it.
 */
enum PermissionOverrideEffectEnum: string
{
    use HasEnumValues;

    case GRANT = 'grant';
    case DENY = 'deny';
}
