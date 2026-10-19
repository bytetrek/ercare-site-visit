<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $google_id
 * @property string|null $avatar
 * @property UserRole $role
 * @property bool $is_active
 * @property Carbon|null $email_verified_at
 * @property string|null $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'google_id', 'avatar', 'role', 'is_active'])]
#[Hidden(['password', 'google_id', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * Determine if the user is an administrator.
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * The facilities this user was given access to on the Facility access page.
     *
     * Only facility managers use these rows. Admins and regional directors
     * see every facility, see seesAllFacilities().
     *
     * @return BelongsToMany<Location, $this, LocationUser>
     */
    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class)
            ->using(LocationUser::class)
            ->withPivot('can_view', 'can_inspect')
            ->withTimestamps()
            ->orderBy('name');
    }

    /**
     * Admins and regional directors can always see and inspect every facility.
     */
    public function seesAllFacilities(): bool
    {
        return $this->role === UserRole::Admin || $this->role === UserRole::RegionalDirector;
    }

    /**
     * Can this user see the facility's reports and issues?
     */
    public function canView(Location $location): bool
    {
        if ($this->seesAllFacilities()) {
            return true;
        }

        $access = $this->locations->firstWhere('id', $location->id);

        if ($access === null) {
            return false;
        }

        return $access->pivot->can_view;
    }

    /**
     * Can this user start inspections and resolve issues at the facility?
     */
    public function canInspect(Location $location): bool
    {
        if ($this->seesAllFacilities()) {
            return true;
        }

        $access = $this->locations->firstWhere('id', $location->id);

        if ($access === null) {
            return false;
        }

        return $access->pivot->can_inspect;
    }

    /**
     * The facilities whose reports and issues this user can see, sorted by name.
     *
     * Inactive facilities are included, so their history stays visible.
     *
     * @return Collection<int, Location>
     */
    public function visibleFacilities(): Collection
    {
        if ($this->seesAllFacilities()) {
            return Location::orderBy('name')->get();
        }

        return $this->locations
            ->filter(fn (Location $location) => $location->pivot->can_view)
            ->values();
    }

    /**
     * The facilities this user can start a new inspection at, sorted by name.
     *
     * Only active facilities are included.
     *
     * @return Collection<int, Location>
     */
    public function inspectableFacilities(): Collection
    {
        if ($this->seesAllFacilities()) {
            return Location::where('is_active', true)->orderBy('name')->get();
        }

        return $this->locations
            ->filter(fn (Location $location) => $location->is_active && $location->pivot->can_inspect)
            ->values();
    }

    /**
     * Save what this user can do at a facility.
     *
     * Inspect always includes view. When both are off the access row is removed.
     */
    public function setFacilityAccess(Location $location, bool $canView, bool $canInspect): void
    {
        if ($canInspect) {
            $canView = true;
        }

        if (! $canView) {
            $this->locations()->detach($location->id);
        } else {
            $this->locations()->syncWithoutDetaching([
                $location->id => [
                    'can_view' => $canView,
                    'can_inspect' => $canInspect,
                ],
            ]);
        }

        // Forget the loaded relation so the next canView()/canInspect() call sees the change.
        $this->unsetRelation('locations');
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }
}
