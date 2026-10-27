<div wire:key="announcement-popup-{{ $current->id }}"
    class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/50 p-4 dark:bg-gray-950/75">
    <div role="dialog" aria-modal="true" aria-labelledby="announcement-popup-title-{{ $current->id }}"
        aria-describedby="announcement-popup-content-{{ $current->id }}" x-data x-trap.noscroll="true"
        style="{{ \Illuminate\Support\Arr::toCssStyles([\Filament\Support\get_color_css_variables($current->color, shades: [50, 100, 400, 500, 600])]) }}"
        class="w-full max-w-md rounded-xl bg-white p-6 text-center shadow-xl ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="mx-auto flex size-12 items-center justify-center rounded-full"
            style="background-color: var(--color-100); color: var(--color-600);">
            <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::OutlinedExclamationCircle" class="size-6" />
        </div>

        <h2 id="announcement-popup-title-{{ $current->id }}"
            class="mt-4 text-base font-semibold text-gray-950 dark:text-white">{{ $current->title }}</h2>

        <p id="announcement-popup-content-{{ $current->id }}"
            class="mt-2 whitespace-pre-line text-sm text-gray-500 dark:text-gray-400">{{ $current->content }}</p>

        @if ($total > 1)
            <p class="mt-4 text-xs text-gray-400 dark:text-gray-500">
                {{ __('backstage/announcements::backstage-announcements.popup.counter', ['current' => $position, 'total' => $total]) }}
            </p>
        @endif

        <button type="button" wire:click="confirm({{ $current->id }})" wire:loading.attr="disabled"
            x-init="$nextTick(() => $el.focus())"
            class="mt-6 w-full rounded-lg px-3 py-2 text-sm font-semibold text-white shadow-sm disabled:opacity-70"
            style="background-color: var(--color-600);">
            {{ __('backstage/announcements::backstage-announcements.popup.confirm') }}
        </button>
    </div>
</div>
