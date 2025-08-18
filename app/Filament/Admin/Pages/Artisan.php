<?php

namespace App\Filament\Admin\Pages;

use Gate;
use Exception;
use Filament\Panel;
use App\Models\Command;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Contracts\HasActions;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Actions\Concerns\InteractsWithActions;
use Symfony\Component\Console\Output\BufferedOutput;
use TomatoPHP\FilamentDeveloperGate\Http\Middleware\DeveloperGateMiddleware;

class Artisan extends Page implements HasTable, HasActions
{
    use InteractsWithTable;
    use InteractsWithActions;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-command-line';

    protected string $view = 'filament.admin.pages.artisan.index';

    public static function getRouteMiddleware(Panel $panel): string|array
    {
        $middlewares = [
            'auth',
            'verified',
        ];

        if (config('filament-artisan.developer_gate', true)) {
            $middlewares[] = DeveloperGateMiddleware::class;
        }

        return $middlewares;
    }

    public static function canAccess(): bool {
        if (Auth::check()) {
            if (Auth::user()->isSuperAdmin()) {
                if (Auth::user()->email == config('app.admin_mail')) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getTitle(): string
    {
        return __('artisan.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('artisan.title');
    }

    /**
     * @param string|null $navigationGroup
     */
    public static function setNavigationGroup(?string $navigationGroup): void
    {
        self::$navigationGroup = __('artisan.group');
    }

    protected function getHeaderActions(): array
    {
        $actions = [
            Action::make('output')
                ->icon('heroicon-s-computer-desktop')
                ->color('warning')
                ->schema(fn(array $arguments = []) => [
                    Textarea::make('output')
                        ->autosize()
                        ->default($arguments ? $arguments['output'] : session()->get('terminal_output'))
                        ->disabled()
                        ->label(__('artisan.actions.output'))
                ])
                ->label(__('artisan.actions.output')),
        ];

        if (config('filament-artisan.developer_gate', true)) {
            $actions[] = Action::make('developer_gate_logout')
                ->icon('heroicon-s-arrow-left-on-rectangle')
                ->action(function () {
                    session()->forget('developer_password');

                    Notification::make()
                        ->title(__('artisan.notifications.logout.title'))
                        ->body(__('artisan.notifications.logout.body'))
                        ->success()
                        ->send();

                    return redirect()->to(config('filament-developer-gate.route_prefix') . '/developer-gate');
                })
                ->requiresConfirmation()
                ->color('danger')
                ->label(trans('filament-developer-gate::messages.logout'));
        }

        return $actions;
    }

    public function runAction(?Command $item = null)
    {
        return Action::make('runAction')
            ->label(__('artisan.modal.label'))
            ->requiresConfirmation()
            ->view('filament.admin.pages.artisan.actions.run')
            ->viewData(['item' => $item])
            ->schema(function (array $arguments = []) {
                $form = [];
                $commandArguments = $arguments['item']['arguments'] != 'null' ? json_decode($arguments['item']['arguments']) : [];
                $commandOptions = $arguments['item']['options'] != 'null' ? json_decode($arguments['item']['options']) : [];
                $formBuild = [];
                foreach ($commandArguments as $arg) {
                    $formBuild[] = $arg;
                }
                foreach ($commandOptions as $opt) {
                    $opt->required = false;
                    $formBuild[] = $opt;
                }

                foreach ($formBuild as $formItem) {
                    if ($formItem->array) {
                        $form[] = TagsInput::make($formItem->name)
                            ->hint($formItem->description)
                            ->label($formItem->title)
                            ->default($formItem->default)
                            ->required($formItem->required);
                    } else {
                        $form[] = TextInput::make($formItem->name)
                            ->password($formItem->name === 'password' ? true : false)
                            ->email($formItem->name === 'email' ? true : false)
                            ->tel($formItem->name === 'phone' ? true : false)
                            ->hint($formItem->description)
                            ->default($formItem->default)
                            ->label($formItem->title)
                            ->required($formItem->required);
                    }
                }

                return $form;
            })
            ->action(function (array $arguments = [], array $data = []) {
                $output = $this->runCommand($arguments['item']['name'], $data);
                $this->replaceMountedAction('output', ['output' => $output]);
            });
    }


    public static function shouldRegisterNavigation(): bool
    {
        $show = true;
        if (config('filament-artisan.navigation.show-only-commands-showing', false)) {
            $local = App::environment('local');
            $only = config('filament-artisan.local', true);
            $show = ($local || !$only);
        }

        return $show;
    }

    public static function getNavigationGroup(): ?string
    {
        return strval(__(config('filament-artisan.navigation.group') ?? static::$navigationGroup));
    }

    public static function getNavigationIcon(): ?string
    {
        return strval(__(config('filament-artisan.navigation.icon') ?? static::$navigationIcon));
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Command::query())
            ->paginated(false)
            ->content(fn() => view('filament.admin.pages.artisan.table.content'))
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('group')
                    ->label(__('artisan.filter_by_group'))
                    ->searchable()
                    ->options(
                        Command::query()
                            ->select('group')
                            ->distinct()
                            ->get()
                            ->pluck('group')
                            ->mapWithKeys(fn($group) => [$group => $group])
                    )
            ])
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('description'),
                TextColumn::make('synopsis'),
                TextColumn::make('arguments'),
                TextColumn::make('options'),
                TextColumn::make('group')->searchable()->sortable(),
                TextColumn::make('error'),
            ]);
    }

    protected function findCommandOrFail(string $name): \Symfony\Component\Console\Command\Command
    {
        $commands = \Illuminate\Support\Facades\Artisan::all();

        if (!in_array($name, array_keys($commands))) {
            abort(404);
        }

        return $commands[$name];
    }


    public function runCommand(string $key, array $data = []): string
    {
        try {

            $command = $this->findCommandOrFail($key);

            $permissions = config('filament-artisan.permissions', []);

            if (count($permissions)) {
                if (in_array($command->getName(), array_keys($permissions)) && !Gate::check($permissions[$command->getName()])) {
                    abort(403);
                }
            }

            $params = [];

            if (count($data)) {
                $data = array_filter($data);
                $options = array_keys($command->getDefinition()->getOptions());

                foreach ($data as $key => $value) {

                    if (in_array($key, $options))
                        $key = "--{$key}";

                    $params[$key] = $value;
                }
            }


            $output = new BufferedOutput();
            $status = \Illuminate\Support\Facades\Artisan::call($command->getName(), $params, $output);
            $output = $output->fetch();

            session()->forget('terminal_output');
            session()->put('terminal_output', $output);

            Notification::make()
                ->title(__('artisan.notifications.success.title'))
                ->body(__('artisan.notifications.success.body'))
                ->success()
                ->send();

            return $output;
        } catch (Exception $exception) {
            session()->forget('terminal_output');
            session()->put('terminal_output', $exception->getMessage());

            Notification::make()
                ->title(__('artisan.notifications.error.title'))
                ->body(__('artisan.notifications.error.body'))
                ->danger()
                ->send();

            return $exception->getMessage();
        }
    }
}
