<x-filament-panels::page>
    {{ $this->form }}

    @php($nextType = $this->nextType())

    <div
        x-data="{
            loading: false,
            register() {
                if (! navigator.geolocation) {
                    $wire.notifyLocationError(@js(__('check_ins.geo_unsupported')))
                    return
                }

                this.loading = true

                navigator.geolocation.getCurrentPosition(
                    async (position) => {
                        await $wire.record(position.coords.latitude, position.coords.longitude, position.coords.accuracy)
                        this.loading = false
                    },
                    async (error) => {
                        const messages = {
                            1: @js(__('check_ins.geo_denied')),
                            2: @js(__('check_ins.geo_unavailable')),
                            3: @js(__('check_ins.geo_timeout')),
                        }
                        await $wire.notifyLocationError(messages[error.code] ?? @js(__('check_ins.geo_unknown')))
                        this.loading = false
                    },
                    { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 },
                )
            },
        }"
        class="flex justify-center"
    >
        <x-filament::button
            size="xl"
            :color="$nextType === \App\Enums\CheckInTypeEnum::In ? 'success' : 'warning'"
            x-on:click="register()"
            x-bind:disabled="loading"
            class="w-full sm:w-auto"
        >
            <span x-show="! loading">{{ $nextType === \App\Enums\CheckInTypeEnum::In ? __('check_ins.register_in') : __('check_ins.register_out') }}</span>
            <span x-show="loading" x-cloak>{{ __('check_ins.getting_location') }}</span>
        </x-filament::button>
    </div>

    <x-filament::section :heading="__('check_ins.recent')">
        @forelse ($this->recentCheckIns() as $checkIn)
            <div class="flex items-center justify-between gap-4 py-2 text-sm">
                <span class="font-medium">{{ $checkIn->type->label() }}</span>
                <span>{{ $checkIn->recorded_at->format('d/m/Y H:i') }}</span>
                <span class="text-gray-500">{{ $checkIn->project?->name ?? '-' }}</span>
                <a href="{{ $checkIn->mapsUrl() }}" target="_blank" rel="noopener" class="text-primary-600">{{ __('check_ins.view_on_map') }}</a>
            </div>
        @empty
            <p class="text-sm text-gray-500">{{ __('check_ins.no_records') }}</p>
        @endforelse
    </x-filament::section>
</x-filament-panels::page>
