<div class="mx-auto flex w-full max-w-6xl flex-col gap-5">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ __('SignatureCare facilities') }}</flux:heading>
            <flux:text class="mt-1 max-w-2xl">
                {{ __('Active facilities appear in the "Start inspection" list. Departments like Pharmacy and Lab are sections of the checklist, not facilities.') }}
            </flux:text>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="addFacility">
            {{ __('Add facility') }}
        </flux:button>
    </div>

    @if ($locations->isEmpty())
        <div class="flex flex-col items-center gap-2 rounded-2xl border border-dashed border-ink-200 bg-white px-6 py-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon.building-office-2 class="size-8 text-ink-300" />
            <flux:heading>{{ __('No facilities yet') }}</flux:heading>
            <flux:text>{{ __('Add your first SignatureCare facility to start inspecting it.') }}</flux:text>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($locations as $location)
                <div
                    wire:key="location-{{ $location->id }}"
                    @class([
                        'flex flex-col rounded-2xl border border-ink-200/80 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900',
                        'opacity-70' => ! $location->is_active,
                    ])
                >
                    <div class="flex items-start gap-3">
                        <span @class([
                            'grid size-10 shrink-0 place-items-center rounded-xl',
                            'bg-brand-50 text-brand-700 dark:bg-brand-900/40 dark:text-brand-300' => $location->is_active,
                            'bg-ink-100 text-ink-400 dark:bg-zinc-800' => ! $location->is_active,
                        ])>
                            <flux:icon.building-office-2 class="size-5" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="font-bold text-ink-900 dark:text-white">{{ $location->name }}</div>
                            <div class="truncate text-xs text-ink-500 dark:text-zinc-400">
                                {{ collect([$location->city, $location->state])->filter()->implode(', ') }}
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-1.5">
                        @if ($location->name_to_be_confirmed)
                            <flux:badge size="sm" color="amber" rounded>{{ __('Name to be confirmed') }}</flux:badge>
                        @endif

                        @if ($location->is_active)
                            <flux:badge size="sm" color="emerald" rounded>{{ __('Active') }}</flux:badge>
                        @else
                            <flux:badge size="sm" color="zinc" rounded>{{ __('Inactive') }}</flux:badge>
                        @endif
                    </div>

                    <dl class="mt-4 border-t border-ink-100 pt-3 text-xs dark:border-zinc-700">
                        <dt class="text-ink-500 dark:text-zinc-400">{{ __('Managers') }}</dt>
                        <dd class="font-bold tabular-nums text-ink-900 dark:text-white">{{ $location->managers_count }}</dd>
                    </dl>

                    <div class="mt-4 flex gap-2">
                        <flux:button size="sm" class="flex-1" wire:click="editFacility({{ $location->id }})">
                            {{ __('Edit') }}
                        </flux:button>
                        <flux:button size="sm" class="flex-1" wire:click="toggleActive({{ $location->id }})">
                            {{ $location->is_active ? __('Deactivate') : __('Activate') }}
                        </flux:button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <flux:modal name="facility-form" class="w-full max-w-md">
        <form wire:submit="saveFacility" class="flex flex-col gap-5">
            <flux:heading size="lg">
                {{ $editingLocationId === null ? __('Add facility') : __('Edit facility') }}
            </flux:heading>

            <flux:input wire:model="name" :label="__('Facility name')" :placeholder="__('e.g. Midland')" required />

            <flux:input wire:model="address" :label="__('Street address')" />

            <div class="grid grid-cols-[1fr_5rem] gap-3">
                <flux:input wire:model="city" :label="__('City')" />
                <flux:input wire:model="state" :label="__('State')" maxlength="2" class:input="uppercase" required />
            </div>

            <flux:checkbox
                wire:model="nameToBeConfirmed"
                :label="__('Name to be confirmed')"
                :description="__('Tick this while the client still has to confirm the facility’s name.')"
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
