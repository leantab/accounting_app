<?php

namespace App\Filament\Pages;

use App\Enums\CheckInTypeEnum;
use App\Models\CheckIn;
use App\Models\Project;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

/**
 * @property-read Schema $form
 */
class RegisterCheckIn extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $navigationLabel = 'Registrar asistencia';

    protected static ?string $title = 'Registrar asistencia';

    protected static ?string $slug = 'registrar-asistencia';

    protected string $view = 'filament.pages.register-check-in';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && CheckIn::canBeRecordedBy($user);
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('project_id')
                    ->label('Proyecto (opcional)')
                    ->options(fn (): array => Project::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),
                Textarea::make('notes')
                    ->label('Notas (opcional)')
                    ->rows(2)
                    ->maxLength(500),
            ])
            ->statePath('data');
    }

    public function nextType(): CheckInTypeEnum
    {
        $last = $this->ownCheckIns()->first();

        return $last === null ? CheckInTypeEnum::In : $last->type->next();
    }

    public function record(float $latitude, float $longitude, ?float $accuracy = null): void
    {
        /** @var User $user */
        $user = Filament::auth()->user();

        abort_unless(CheckIn::canBeRecordedBy($user), 403);

        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            $this->notifyLocationError('La ubicación recibida no es válida.');

            return;
        }

        $data = $this->form->getState();

        $projectId = filled($data['project_id'] ?? null) ? (int) $data['project_id'] : null;

        abort_if($projectId !== null && ! Project::query()->whereKey($projectId)->exists(), 403);

        $type = $this->nextType();

        CheckIn::query()->create([
            'customer_id' => $user->customer_id,
            'user_id' => $user->id,
            'project_id' => $projectId,
            'type' => $type,
            'recorded_at' => now(),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy_m' => $accuracy === null ? null : (int) round($accuracy),
            'notes' => $data['notes'] ?? null,
        ]);

        $this->form->fill();

        Notification::make()
            ->success()
            ->title($type === CheckInTypeEnum::In ? 'Entrada registrada' : 'Salida registrada')
            ->send();
    }

    public function notifyLocationError(string $message): void
    {
        Notification::make()
            ->danger()
            ->title('No se pudo registrar la asistencia')
            ->body($message)
            ->send();
    }

    /**
     * @return Collection<int, CheckIn>
     */
    public function recentCheckIns(): Collection
    {
        return $this->ownCheckIns()->take(10)->values();
    }

    /**
     * @return Collection<int, CheckIn>
     */
    private function ownCheckIns(): Collection
    {
        return CheckIn::query()
            ->with('project')
            ->where('user_id', Filament::auth()->id())
            ->latest('recorded_at')
            ->latest('id')
            ->limit(10)
            ->get();
    }
}
