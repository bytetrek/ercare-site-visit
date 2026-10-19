<?php

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Models\Location;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The SignatureCare facilities (the `locations` table).
 */
#[Title('Facilities')]
class LocationManagement extends Component
{
    /**
     * The facility being edited in the modal, or null when adding a new one.
     */
    public ?int $editingLocationId = null;

    public string $name = '';

    public string $address = '';

    public string $city = '';

    public string $state = 'TX';

    public bool $nameToBeConfirmed = false;

    /**
     * Open the modal to add a new facility.
     */
    public function addFacility(): void
    {
        Gate::authorize('manage-users');

        $this->resetValidation();
        $this->reset('editingLocationId', 'name', 'address', 'city', 'state', 'nameToBeConfirmed');

        Flux::modal('facility-form')->show();
    }

    /**
     * Open the modal to edit an existing facility.
     */
    public function editFacility(int $locationId): void
    {
        Gate::authorize('manage-users');

        $location = Location::findOrFail($locationId);

        $this->resetValidation();
        $this->editingLocationId = $location->id;
        $this->name = $location->name;
        $this->address = $location->address ?? '';
        $this->city = $location->city ?? '';
        $this->state = $location->state;
        $this->nameToBeConfirmed = $location->name_to_be_confirmed;

        Flux::modal('facility-form')->show();
    }

    /**
     * Save the modal: create a new facility or update the one being edited.
     */
    public function saveFacility(): void
    {
        Gate::authorize('manage-users');

        $this->name = trim($this->name);
        $this->address = trim($this->address);
        $this->city = trim($this->city);
        $this->state = strtoupper(trim($this->state));

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('locations', 'name')->ignore($this->editingLocationId)],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['required', 'string', 'size:2', 'alpha'],
            'nameToBeConfirmed' => ['boolean'],
        ], [
            'name.unique' => __('There is already a facility with that name.'),
        ], [
            'name' => __('facility name'),
            'address' => __('street address'),
        ]);

        $details = [
            'name' => $validated['name'],
            'address' => $validated['address'] === '' ? null : $validated['address'],
            'city' => $validated['city'] === '' ? null : $validated['city'],
            'state' => $validated['state'],
            'name_to_be_confirmed' => $validated['nameToBeConfirmed'],
        ];

        if ($this->editingLocationId === null) {
            Location::create($details);

            Flux::toast(variant: 'success', text: __(':name added. Give managers access in Facility access.', ['name' => $details['name']]));
        } else {
            $location = Location::findOrFail($this->editingLocationId);
            $location->update($details);

            Flux::toast(variant: 'success', text: __(':name updated.', ['name' => $details['name']]));
        }

        Flux::modal('facility-form')->close();

        $this->reset('editingLocationId', 'name', 'address', 'city', 'state', 'nameToBeConfirmed');
    }

    /**
     * Activate or deactivate a facility. Inactive facilities keep their history.
     */
    public function toggleActive(int $locationId): void
    {
        Gate::authorize('manage-users');

        $location = Location::findOrFail($locationId);
        $location->is_active = ! $location->is_active;
        $location->save();

        if ($location->is_active) {
            Flux::toast(text: __(':name is active again.', ['name' => $location->name]));
        } else {
            Flux::toast(text: __(':name is hidden from new inspections. Its history is kept.', ['name' => $location->name]));
        }
    }

    public function render(): View
    {
        // "Managers" = active facility managers who can inspect the facility.
        $locations = Location::withCount(['users as managers_count' => function ($query) {
            $query->where('users.role', UserRole::FacilityManager)
                ->where('users.is_active', true)
                ->where('location_user.can_inspect', true);
        }])
            ->orderBy('name')
            ->get();

        return view('livewire.admin.location-management', [
            'locations' => $locations,
        ]);
    }
}
