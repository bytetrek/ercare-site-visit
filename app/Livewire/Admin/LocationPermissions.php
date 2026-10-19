<?php

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Models\Location;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The checkbox matrix of which facility manager can view or inspect which facility.
 */
#[Title('Facility access')]
class LocationPermissions extends Component
{
    /**
     * Click on "View". Turning view off also turns inspect off.
     */
    public function toggleView(int $userId, int $locationId): void
    {
        Gate::authorize('manage-users');

        $manager = User::where('role', UserRole::FacilityManager)->findOrFail($userId);
        $location = Location::findOrFail($locationId);

        $canView = ! $manager->canView($location);
        $canInspect = $manager->canInspect($location);

        if (! $canView) {
            $canInspect = false;
        }

        $manager->setFacilityAccess($location, $canView, $canInspect);

        $this->confirmChange($manager, $location);
    }

    /**
     * Click on "Inspect". Turning inspect on also turns view on.
     */
    public function toggleInspect(int $userId, int $locationId): void
    {
        Gate::authorize('manage-users');

        $manager = User::where('role', UserRole::FacilityManager)->findOrFail($userId);
        $location = Location::findOrFail($locationId);

        $canView = $manager->canView($location);
        $canInspect = ! $manager->canInspect($location);

        if ($canInspect) {
            $canView = true;
        }

        $manager->setFacilityAccess($location, $canView, $canInspect);

        $this->confirmChange($manager, $location);
    }

    /**
     * Show a toast like "Maria Gonzalez can inspect Frisco."
     */
    protected function confirmChange(User $manager, Location $location): void
    {
        if ($manager->canInspect($location)) {
            $text = __(':name can inspect :facility.', ['name' => $manager->name, 'facility' => $location->name]);
        } elseif ($manager->canView($location)) {
            $text = __(':name can view :facility.', ['name' => $manager->name, 'facility' => $location->name]);
        } else {
            $text = __(':name has no access to :facility.', ['name' => $manager->name, 'facility' => $location->name]);
        }

        Flux::toast(text: $text);
    }

    public function render(): View
    {
        $locations = Location::where('is_active', true)->orderBy('name')->get();

        // Admins and regional directors see every facility, so they only get a summary row.
        $seeAllUsers = User::where('is_active', true)
            ->whereIn('role', [UserRole::Admin, UserRole::RegionalDirector])
            ->orderBy('name')
            ->get();

        $managers = User::with('locations')
            ->where('is_active', true)
            ->where('role', UserRole::FacilityManager)
            ->orderBy('name')
            ->get();

        return view('livewire.admin.location-permissions', [
            'locations' => $locations,
            'seeAllUsers' => $seeAllUsers,
            'managers' => $managers,
        ]);
    }
}
