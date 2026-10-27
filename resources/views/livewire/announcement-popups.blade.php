<div class="ba-popup-overlay">
    <div wire:key="announcement-popup-{{ $current->id }}" role="dialog" aria-modal="true"
        aria-labelledby="announcement-popup-title-{{ $current->id }}"
        aria-describedby="announcement-popup-content-{{ $current->id }}" x-data x-trap.noscroll="true"
        style="{{ \Illuminate\Support\Arr::toCssStyles([\Filament\Support\get_color_css_variables($current->color, shades: [50, 100, 400, 500, 600])]) }}"
        class="ba-popup-panel">
        <div class="ba-popup-icon">
            <x-filament::icon :icon="\Filament\Support\Icons\Heroicon::OutlinedExclamationCircle" />
        </div>

        <h2 id="announcement-popup-title-{{ $current->id }}" class="ba-popup-title">{{ $current->title }}</h2>

        <p id="announcement-popup-content-{{ $current->id }}" class="ba-popup-content whitespace-pre-line">{{ $current->content }}</p>

        @if ($total > 1)
            <p class="ba-popup-counter">
                {{ __('backstage/announcements::backstage-announcements.popup.counter', ['current' => $position, 'total' => $total]) }}
            </p>
        @endif

        <button type="button" wire:click="confirm({{ $current->id }})" wire:loading.attr="disabled"
            x-init="$nextTick(() => $el.focus())" class="ba-popup-button">
            {{ __('backstage/announcements::backstage-announcements.popup.confirm') }}
        </button>
    </div>
</div>
