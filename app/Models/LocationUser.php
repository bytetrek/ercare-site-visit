<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * A facility manager's access to one facility.
 *
 * @property int $user_id
 * @property int $location_id
 * @property bool $can_view
 * @property bool $can_inspect
 */
class LocationUser extends Pivot
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'can_view' => 'boolean',
            'can_inspect' => 'boolean',
        ];
    }
}
