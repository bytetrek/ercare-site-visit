<?php

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * The whitelist of people who may sign in with Google.
 */
#[Title('Users')]
class UserManagement extends Component
{
    /**
     * Whether the "Add user" form is open.
     */
    public bool $adding = false;

    public string $newName = '';

    public string $newEmail = '';

    public string $newRole = 'facility_manager';

    /**
     * The user being edited in the "Edit user" modal.
     */
    public ?int $editingUserId = null;

    public string $editName = '';

    public string $editEmail = '';

    /**
     * Put a new email on the list, so that person can sign in with Google.
     */
    public function addUser(): void
    {
        Gate::authorize('manage-users');

        $this->newEmail = Str::lower(trim($this->newEmail));

        $validated = $this->validate([
            'newName' => ['required', 'string', 'max:255'],
            'newEmail' => ['required', 'email', 'max:255', 'unique:users,email'],
            'newRole' => ['required', Rule::enum(UserRole::class)],
        ], [
            'newEmail.unique' => __('That email is already on the list.'),
        ], [
            'newName' => __('full name'),
            'newEmail' => __('Google email'),
            'newRole' => __('role'),
        ]);

        User::create([
            'name' => trim($validated['newName']),
            'email' => $validated['newEmail'],
            'role' => $validated['newRole'],
            'is_active' => true,
        ]);

        Flux::toast(variant: 'success', text: __(':email can now sign in with Google.', ['email' => $validated['newEmail']]));

        $this->reset('adding', 'newName', 'newEmail', 'newRole');
    }

    /**
     * Open the "Edit user" modal for one user.
     */
    public function editUser(int $userId): void
    {
        Gate::authorize('manage-users');

        $user = User::findOrFail($userId);

        $this->resetValidation();
        $this->editingUserId = $user->id;
        $this->editName = $user->name;
        $this->editEmail = $user->email;

        Flux::modal('edit-user')->show();
    }

    /**
     * Save the name and email from the "Edit user" modal.
     */
    public function updateUser(): void
    {
        Gate::authorize('manage-users');

        $user = User::findOrFail($this->editingUserId);

        $this->editEmail = Str::lower(trim($this->editEmail));

        $validated = $this->validate([
            'editName' => ['required', 'string', 'max:255'],
            'editEmail' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ], [
            'editEmail.unique' => __('That email is already on the list.'),
        ], [
            'editName' => __('full name'),
            'editEmail' => __('Google email'),
        ]);

        $emailChanged = $validated['editEmail'] !== $user->email;

        // A typo in your own email would lock you out, so admins can't change their own.
        if ($emailChanged && $user->is(Auth::user())) {
            $this->addError('editEmail', __('You can’t change your own email.'));

            return;
        }

        // A new email means a different Google account, so the old Google link no longer applies.
        if ($emailChanged) {
            $user->google_id = null;
        }

        $user->name = trim($validated['editName']);
        $user->email = $validated['editEmail'];
        $user->save();

        Flux::modal('edit-user')->close();
        Flux::toast(variant: 'success', text: __(':name updated.', ['name' => $user->name]));

        $this->reset('editingUserId', 'editName', 'editEmail');
    }

    /**
     * Change a user's role from the dropdown in the table.
     */
    public function changeRole(int $userId, string $role): void
    {
        Gate::authorize('manage-users');

        $user = User::findOrFail($userId);
        $newRole = UserRole::tryFrom($role);

        if ($newRole === null) {
            return;
        }

        // An admin can't take away their own admin role, or nobody might be left to manage users.
        if ($user->is(Auth::user())) {
            Flux::toast(variant: 'danger', text: __('You can’t change your own role.'));

            return;
        }

        $user->role = $newRole;
        $user->save();

        Flux::toast(text: __(':name is now a :role.', [
            'name' => $user->name,
            'role' => Str::lower($newRole->label()),
        ]));
    }

    /**
     * Turn a user's access on or off. Turned-off users are signed out on their next click.
     */
    public function toggleActive(int $userId): void
    {
        Gate::authorize('manage-users');

        $user = User::findOrFail($userId);

        if ($user->is(Auth::user())) {
            Flux::toast(variant: 'danger', text: __('You can’t turn off your own access.'));

            return;
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        if ($user->is_active) {
            Flux::toast(variant: 'success', text: __(':name can sign in again.', ['name' => $user->name]));
        } else {
            Flux::toast(text: __(':name can no longer sign in.', ['name' => $user->name]));
        }
    }

    /**
     * The text for the "Facilities" column, e.g. "Frisco, Killeen (view)".
     *
     * Returns an empty string when a facility manager has no facilities yet.
     */
    public function facilitySummary(User $user): string
    {
        $names = [];

        foreach ($user->visibleFacilities() as $location) {
            if ($user->canInspect($location)) {
                $names[] = $location->name;
            } else {
                $names[] = $location->name.' '.__('(view)');
            }
        }

        return implode(', ', $names);
    }

    public function render(): View
    {
        $allUsers = User::with('locations')->orderBy('name')->get();

        // Show active users first, then turned-off users.
        // Inside each group: admins, then regional directors, then facility managers.
        $sortedUsers = [];

        foreach ([true, false] as $isActive) {
            foreach (UserRole::cases() as $role) {
                foreach ($allUsers as $user) {
                    if ($user->is_active === $isActive && $user->role === $role) {
                        $sortedUsers[] = $user;
                    }
                }
            }
        }

        return view('livewire.admin.user-management', [
            'users' => $sortedUsers,
            'roles' => UserRole::cases(),
        ]);
    }
}
