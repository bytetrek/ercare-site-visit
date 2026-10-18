<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => __('Sign in')])
    </head>
    <body class="min-h-screen bg-ink-50 text-ink-900 antialiased dark:bg-ink-950 dark:text-white">
        <div class="min-h-screen lg:grid lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)]">

            {{-- Brand panel --}}
            <section class="relative overflow-hidden bg-ink-900 px-6 py-8 text-white sm:px-10 lg:flex lg:flex-col lg:py-12">
                <div class="pointer-events-none absolute -top-24 -right-24 size-80 rounded-full bg-brand-500/20 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-32 -left-16 size-80 rounded-full bg-rose-500/10 blur-3xl"></div>

                <div class="relative flex items-center gap-3">
                    <span class="grid size-10 place-items-center rounded-xl bg-rose-600 text-white shadow-sm">
                        <svg viewBox="0 0 24 24" class="size-6" fill="currentColor" aria-hidden="true">
                            <path d="M9.5 3h5v6.5H21v5h-6.5V21h-5v-6.5H3v-5h6.5z" />
                        </svg>
                    </span>
                    <span class="leading-tight">
                        <span class="block font-bold">ERCare24</span>
                        <span class="block text-xs text-ink-300">{{ __('Site Visit Portal') }}</span>
                    </span>
                </div>

                <div class="relative mt-8 lg:mt-auto">
                    <p class="text-xs font-semibold tracking-[0.16em] text-brand-300 uppercase">ERCare24 · {{ __('Site Visit Portal') }}</p>
                    <h1 class="mt-3 max-w-md text-3xl leading-tight font-extrabold tracking-tight text-balance sm:text-4xl">
                        {{ __('Monthly facility inspections, done on-site and followed through to resolution.') }}
                    </h1>

                    <ul class="mt-8 hidden max-w-md flex-col gap-4 text-sm text-ink-200 sm:flex">
                        <li class="flex gap-3">
                            <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full bg-white/10 text-brand-300">
                                <flux:icon.clipboard-document-check variant="micro" class="size-3.5" />
                            </span>
                            <span><b class="font-semibold text-white">{{ __('7 sections, one checklist.') }}</b> {{ __('The same checklist as the current Word form, filled in on a phone or tablet.') }}</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full bg-white/10 text-rose-300">
                                <flux:icon.exclamation-triangle variant="micro" class="size-3.5" />
                            </span>
                            <span><b class="font-semibold text-white">{{ __('Every "No" becomes an issue') }}</b> {{ __('that stays open until someone resolves it.') }}</span>
                        </li>
                        <li class="flex gap-3">
                            <span class="mt-0.5 grid size-6 shrink-0 place-items-center rounded-full bg-white/10 text-amber-300">
                                <flux:icon.envelope variant="micro" class="size-3.5" />
                            </span>
                            <span><b class="font-semibold text-white">{{ __('Email on submit') }}</b> {{ __('to the facility manager and regional director.') }}</span>
                        </li>
                    </ul>
                </div>

                <p class="relative mt-10 hidden text-xs text-ink-400 lg:block">sitevisits.ercare24.com</p>
            </section>

            {{-- Sign in --}}
            <section class="flex items-start justify-center px-4 py-10 sm:px-8 lg:items-center">
                <div class="w-full max-w-lg">
                    <flux:heading size="xl" level="2">{{ __('Sign in') }}</flux:heading>
                    <flux:text class="mt-1.5">{{ __('Use your ERCare24 Google account. Only emails approved by an admin can sign in.') }}</flux:text>

                    @error('google')
                        <flux:callout variant="danger" icon="lock-closed" class="mt-6" :heading="$message" />
                    @enderror

                    <flux:button :href="route('auth.google.redirect')" class="mt-6 w-full" data-test="google-login-button">
                        <svg class="size-5" viewBox="0 0 48 48" aria-hidden="true">
                            <path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.4-.4-3.5z" />
                            <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z" />
                            <path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-8l-6.5 5C9.5 39.6 16.2 44 24 44z" />
                            <path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C37 39.2 44 34 44 24c0-1.3-.1-2.4-.4-3.5z" />
                        </svg>

                        {{ __('Sign in with Google') }}
                    </flux:button>
                </div>
            </section>
        </div>

        @fluxScripts
    </body>
</html>
