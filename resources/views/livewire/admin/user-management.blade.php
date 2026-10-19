<div class="mx-auto flex w-full max-w-6xl flex-col gap-5">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('Approved users') }}</flux:heading>
            <flux:text class="mt-1 max-w-2xl">
                {{ __('Only the emails on this list can sign in with Google. Turn a user off and they lose access right away.') }}
            </flux:text>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="$toggle('adding')">
            {{ __('Add user') }}
        </flux:button>
    </div>

    @if ($adding)
        <form
            wire:submit="addUser"
            class="grid gap-3 rounded-2xl border border-brand-200 bg-brand-50/50 p-5 sm:grid-cols-[1fr_1.3fr_1fr_auto] sm:items-start dark:border-brand-800 dark:bg-brand-900/20"
        >
            <flux:input wire:model="newName" :label="__('Full name')" :placeholder="__('e.g. Sam Rivera')" required autofocus />

            <flux:input wire:model="newEmail" type="email" :label="__('Google email')" placeholder="name@ercare24.com" required />

            <flux:select wire:model="newRole" :label="__('Role')">
                @foreach ($roles as $role)
                    <flux:select.option :value="$role->value">{{ $role->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="sm:pt-6.5">
                <flux:button type="submit" variant="primary" color="zinc" class="w-full">
                    {{ __('Add to list') }}
                </flux:button>
            </div>
        </form>
    @endif

    <div class="rounded-2xl border border-ink-200/80 bg-white px-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <flux:table class="min-w-[720px]">
            <flux:table.columns>
                <flux:table.column>{{ __('User') }}</flux:table.column>
                <flux:table.column>{{ __('Role') }}</flux:table.column>
                <flux:table.column>{{ __('Facilities') }}</flux:table.column>
                <flux:table.column>{{ __('Access') }}</flux:table.column>
                <flux:table.column><span class="sr-only">{{ __('Edit') }}</span></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($users as $user)
                    @php
                        $isMe = $user->is(auth()->user());
                    @endphp

                    <flux:table.row :key="$user->id">
                        <flux:table.cell>
                            <div @class(['flex items-center gap-3', 'opacity-60' => ! $user->is_active])>
                                <flux:avatar circle size="sm" :name="$user->name" :initials="$user->initials()" color="auto" :color:seed="$user->id" />

                                <div class="min-w-0">
                                    <div class="font-semibold text-ink-900 dark:text-white">
                                        {{ $user->name }}
                                        @if ($isMe)
                                            <span class="text-xs font-medium text-ink-400">{{ __('(you)') }}</span>
                                        @endif
                                    </div>
                                    <div class="truncate text-xs text-ink-500 dark:text-zinc-400">{{ $user->email }}</div>
                                </div>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:select
                                size="sm"
                                class="w-auto!"
                                wire:change="changeRole({{ $user->id }}, $event.target.value)"
                                :disabled="$isMe"
                                :aria-label="__('Role for :name', ['name' => $user->name])"
                            >
                                @foreach ($roles as $role)
                                    <flux:select.option :value="$role->value" :selected="$user->role === $role">{{ $role->label() }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:table.cell>

                        <flux:table.cell class="whitespace-normal">
                            @if ($user->seesAllFacilities())
                                <span class="text-ink-500 dark:text-zinc-400">{{ __('All facilities') }}</span>
                            @else
                                @php
                                    $facilities = $this->facilitySummary($user);
                                @endphp

                                <flux:link :href="route('admin.access')" variant="subtle" wire:navigate>
                                    {{ $facilities !== '' ? $facilities : __('None yet: set in Facility access') }}
                                </flux:link>
                            @endif
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="flex items-center gap-2.5">
                                <flux:switch
                                    :checked="$user->is_active"
                                    wire:change="toggleActive({{ $user->id }})"
                                    :disabled="$isMe"
                                    :aria-label="__('Access for :name', ['name' => $user->name])"
                                />
                                <span @class([
                                    'text-xs font-semibold',
                                    'text-brand-700 dark:text-brand-300' => $user->is_active,
                                    'text-ink-500 dark:text-zinc-400' => ! $user->is_active,
                                ])>
                                    {{ $user->is_active ? __('Active') : __('Turned off') }}
                                </span>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell align="end">
                            <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="editUser({{ $user->id }})">
                                {{ __('Edit') }}
                            </flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    </div>

    <flux:modal name="edit-user" class="w-full max-w-md">
        <form wire:submit="updateUser" class="flex flex-col gap-5">
            <flux:heading size="lg">{{ __('Edit user') }}</flux:heading>

            <flux:input wire:model="editName" :label="__('Full name')" required />

            <flux:input
                wire:model="editEmail"
                type="email"
                :label="__('Google email')"
                :description="__('If you change the email, they sign in with the new Google account from now on.')"
                required
            />

            <div class="grid grid-cols-2 gap-3">
                <flux:modal.close>
                    <flux:button class="w-full">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" class="w-full">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
