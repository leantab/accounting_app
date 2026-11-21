<div
    x-data="{
        installEvent: null,
        installed: window.matchMedia('(display-mode: standalone)').matches,
        async install() {
            if (! this.installEvent) {
                return
            }

            this.installEvent.prompt()
            await this.installEvent.userChoice
            this.installEvent = null
        },
    }"
    x-on:beforeinstallprompt.window.prevent="installEvent = $event"
    x-on:appinstalled.window="installEvent = null; installed = true"
    x-show="installEvent && ! installed"
    x-cloak
>
    <x-filament::button
        color="gray"
        size="sm"
        icon="heroicon-o-arrow-down-tray"
        x-on:click="install()"
    >
        {{ __('common.install_app') }}
    </x-filament::button>
</div>
