<div class="mx-auto flex w-full max-w-7xl flex-col gap-5">
    <div>
        <flux:heading size="xl" level="1">{{ __('Who can see and inspect each facility') }}</flux:heading>
        <flux:text class="mt-1 max-w-3xl">
            <strong class="font-semibold text-ink-700 dark:text-white">{{ __('View') }}</strong> {{ __('lets a manager see reports and issues.') }}
            <strong class="font-semibold text-ink-700 dark:text-white">{{ __('Inspect') }}</strong> {{ __('also lets them start inspections and resolve issues.') }}
            {{ __('Admins and regional directors always have access to every facility.') }}
        </flux:text>
    </div>

    <div class="flex flex-wrap gap-4 text-xs text-ink-600 dark:text-zinc-300">
        <span class="flex items-center gap-1.5">
            <span class="grid size-5 place-items-center rounded bg-brand-600 text-white"><flux:icon.check variant="micro" class="size-3" /></span>
            {{ __('Inspect (includes view)') }}
        </span>
        <span class="flex items-center gap-1.5">
            <span class="grid size-5 place-items-center rounded bg-sky-100 text-sky-700 ring-1 ring-inset ring-sky-300"><flux:icon.check variant="micro" class="size-3" /></span>
            {{ __('View only') }}
        </span>
        <span class="flex items-center gap-1.5">
            <span class="size-5 rounded ring-1 ring-inset ring-ink-200 dark:ring-zinc-600"></span>
            {{ __('No access') }}
        </span>
    </div>

    @if ($locations->isEmpty())
        <div class="flex flex-col items-center gap-2 rounded-2xl border border-dashed border-ink-200 bg-white px-6 py-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon.building-office-2 class="size-8 text-ink-300" />
            <flux:heading>{{ __('No active facilities') }}</flux:heading>
            <flux:text>
                {{ __('Add or activate a facility on the') }}
                <flux:link :href="route('admin.facilities')" wire:navigate>{{ __('Facilities page') }}</flux:link>{{ __(', then give managers access here.') }}
            </flux:text>
        </div>
    @else
        <div class="rounded-2xl border border-ink-200/80 bg-white px-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <flux:table>
                <flux:table.columns>
                    <flux:table.column sticky class="min-w-48 bg-white dark:bg-zinc-900">{{ __('User') }}</flux:table.column>
                    @foreach ($locations as $location)
                        <flux:table.column align="center" class="min-w-30">
                            <div class="flex flex-col items-center">
                                <span>{{ $location->name }}</span>
                                @if ($location->name_to_be_confirmed)
                                    <span class="text-[10px] font-medium text-amber-700 dark:text-amber-400">{{ __('to be confirmed') }}</span>
                                @endif
                            </div>
                        </flux:table.column>
                    @endforeach
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($seeAllUsers as $user)
                        <flux:table.row :key="'all-'.$user->id">
                            <flux:table.cell sticky class="bg-white dark:bg-zinc-900">
                                <div class="font-semibold text-ink-700 dark:text-white">{{ $user->name }}</div>
                                <div class="text-xs text-ink-500 dark:text-zinc-400">{{ $user->role->label() }}</div>
                            </flux:table.cell>
                            <flux:table.cell :colspan="$locations->count()" align="center" class="bg-ink-50/60 text-xs font-medium dark:bg-zinc-800/60">
                                {{ __('All facilities, automatically') }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach

                    @forelse ($managers as $manager)
                        <flux:table.row :key="'manager-'.$manager->id">
                            <flux:table.cell sticky class="bg-white dark:bg-zinc-900">
                                <div class="flex items-center gap-2.5">
                                    <flux:avatar circle size="sm" :name="$manager->name" :initials="$manager->initials()" color="auto" :color:seed="$manager->id" />
                                    <div class="min-w-0">
                                        <div class="truncate font-semibold text-ink-900 dark:text-white">{{ $manager->name }}</div>
                                        <div class="truncate text-xs text-ink-500 dark:text-zinc-400">{{ $manager->email }}</div>
                                    </div>
                                </div>
                            </flux:table.cell>

                            @foreach ($locations as $location)
                                @php
                                    $canView = $manager->canView($location);
                                    $canInspect = $manager->canInspect($location);
                                @endphp

                                <flux:table.cell align="center" wire:key="cell-{{ $manager->id }}-{{ $location->id }}">
                                    <flux:button.group class="w-fit">
                                        @if ($canInspect)
                                            <flux:button size="xs" variant="primary" wire:click="toggleView({{ $manager->id }}, {{ $location->id }})" aria-pressed="true" :aria-label="__('View :facility for :name', ['facility' => $location->name, 'name' => $manager->name])">{{ __('View') }}</flux:button>
                                        @elseif ($canView)
                                            <flux:button size="xs" variant="filled" color="sky" wire:click="toggleView({{ $manager->id }}, {{ $location->id }})" aria-pressed="true" :aria-label="__('View :facility for :name', ['facility' => $location->name, 'name' => $manager->name])">{{ __('View') }}</flux:button>
                                        @else
                                            <flux:button size="xs" wire:click="toggleView({{ $manager->id }}, {{ $location->id }})" aria-pressed="false" :aria-label="__('View :facility for :name', ['facility' => $location->name, 'name' => $manager->name])">{{ __('View') }}</flux:button>
                                        @endif

                                        @if ($canInspect)
                                            <flux:button size="xs" variant="primary" wire:click="toggleInspect({{ $manager->id }}, {{ $location->id }})" aria-pressed="true" :aria-label="__('Inspect :facility for :name', ['facility' => $location->name, 'name' => $manager->name])">{{ __('Inspect') }}</flux:button>
                                        @else
                                            <flux:button size="xs" wire:click="toggleInspect({{ $manager->id }}, {{ $location->id }})" aria-pressed="false" :aria-label="__('Inspect :facility for :name', ['facility' => $location->name, 'name' => $manager->name])">{{ __('Inspect') }}</flux:button>
                                        @endif
                                    </flux:button.group>
                                </flux:table.cell>
                            @endforeach
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell :colspan="$locations->count() + 1" align="center" class="whitespace-normal py-8">
                                {{ __('No facility managers yet. Add them on the') }}
                                <flux:link :href="route('admin.users')" wire:navigate>{{ __('Users page') }}</flux:link>{{ __(', then give them access here.') }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>

        <flux:text size="sm">{{ __('Changes save as soon as you click.') }}</flux:text>
    @endif
</div>
