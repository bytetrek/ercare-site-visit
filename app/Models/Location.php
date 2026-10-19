<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * A SignatureCare emergency center. Shown as "Facility" in the UI.
 *
 * @property int $id
 * @property string $name
 * @property string|null $address
 * @property string|null $city
 * @property string $state
 * @property bool $is_active
 * @property bool $name_to_be_confirmed
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'address', 'city', 'state', 'is_active', 'name_to_be_confirmed'])]
class Location extends Model
{
    /** @use HasFactory<LocationFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'name_to_be_confirmed' => 'boolean',
        ];
    }

    /**
     * The facility managers who were given access to this facility.
     *
     * @return BelongsToMany<User, $this, LocationUser>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(LocationUser::class)
            ->withPivot('can_view', 'can_inspect')
            ->withTimestamps();
    }
}
