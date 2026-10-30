<x-filament-panels::page>
    {{ $this->form }}

    @php($nextType = $this->nextType())

    <div
        x-data="{
            loading: false,
            register() {
                if (! navigator.geolocation) {
                    $wire.notifyLocationError('Este dispositivo no permite obtener la ubicación.')
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
                            1: 'Debe permitir el acceso a la ubicación en el navegador para registrar la asistencia.',
                            2: 'No se pudo determinar la ubicación. Verifique que el GPS esté activado.',
                            3: 'La ubicación tardó demasiado en responder. Intente nuevamente.',
                        }
                        await $wire.notifyLocationError(messages[error.code] ?? 'No se pudo obtener la ubicación.')
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
            <span x-show="! loading">Registrar {{ strtolower($nextType->label()) }}</span>
            <span x-show="loading" x-cloak>Obteniendo ubicación...</span>
        </x-filament::button>
    </div>

    <x-filament::section heading="Últimos registros">
        @forelse ($this->recentCheckIns() as $checkIn)
            <div class="flex items-center justify-between gap-4 py-2 text-sm">
                <span class="font-medium">{{ $checkIn->type->label() }}</span>
                <span>{{ $checkIn->recorded_at->format('d/m/Y H:i') }}</span>
                <span class="text-gray-500">{{ $checkIn->project?->name ?? '-' }}</span>
                <a href="{{ $checkIn->mapsUrl() }}" target="_blank" rel="noopener" class="text-primary-600">Ver en mapa</a>
            </div>
        @empty
            <p class="text-sm text-gray-500">Todavía no registró ninguna asistencia.</p>
        @endforelse
    </x-filament::section>
</x-filament-panels::page>
