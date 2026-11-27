<?php

namespace App\Filament\App\Resources\Wishlists\Concerns;

use App\Models\Department;
use App\Models\OrderArticle;
use App\Models\User;
use App\Models\Wishlist;
use App\Services\WishlistManager;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;

trait InteractsWithWishlists
{
    public ?int $previewWishlistId = null;

    public function wishlistTarget(): ?Wishlist
    {
        return Gate::allows('viewAny', Wishlist::class) ? app(WishlistManager::class)->target(auth()->user()) : null;
    }

    public function previewWishlist(): ?Wishlist
    {
        if ($this->previewWishlistId) {
            $wishlist = Wishlist::findOrFail($this->previewWishlistId);
            Gate::authorize('view', $wishlist);

            return $wishlist;
        }

        return $this->wishlistTarget();
    }

    public function wishlistsAction(): Action
    {
        return Action::make('wishlists')
            ->label(fn (): string => __('wishlists.title').' · '.($this->wishlistTarget()?->items_count ?? 0))
            ->icon('heroicon-o-bookmark-square')->color('gray')
            ->authorize(fn (): bool => Gate::allows('viewAny', Wishlist::class))
            ->slideOver()->modalHeading(__('wishlists.quick_access'))->modalSubmitAction(false)
            ->modalCancelActionLabel(__('wishlists.close'))
            ->modalContent(fn (): View => view('filament.app.wishlists.preview', [
                'wishlist' => $this->previewWishlist(),
                'lists' => app(WishlistManager::class)->lists(auth()->user())->get(),
                'editableIds' => app(WishlistManager::class)->lists(auth()->user(), true)->pluck('wishlists.id')->all(),
            ]));
    }

    public function wishlistTargetAction(): Action
    {
        return Action::make('wishlistTarget')->label(fn (): string => __('wishlists.target').': '.($this->wishlistTarget()?->name ?? __('wishlists.choose')))
            ->icon('heroicon-o-chevron-down')->color('gray')
            ->authorize(fn (): bool => Gate::allows('viewAny', Wishlist::class) && (Gate::allows('manage-all-Wishlist') || auth()->user()->checkPermissionTo('update-Wishlist')))
            ->schema([
                Select::make('wishlist_id')->label(__('wishlists.target'))->required()->searchable()
                    ->options(fn (): array => app(WishlistManager::class)->lists(auth()->user(), true)->get()->mapWithKeys(fn (Wishlist $list): array => [$list->id => $list->name.' ('.$list->items_count.')'])->all())
                    ->default(fn (): ?int => $this->wishlistTarget()?->id),
            ])->action(function (array $data): void {
                app(WishlistManager::class)->select(auth()->user(), (int) $data['wishlist_id']);
                $this->previewWishlistId = null;
                $this->refreshWishlistCards();
            });
    }

    public function newWishlistAction(): Action
    {
        return Action::make('newWishlist')->label(__('wishlists.new_list'))->icon('heroicon-o-plus')
            ->authorize(fn (): bool => Gate::allows('create', Wishlist::class))
            ->schema([
                TextInput::make('name')->label(__('general.name'))->required()->maxLength(255),
                Textarea::make('description')->label(__('general.description'))->maxLength(10000),
            ])->action(function (array $data): void {
                $wishlist = app(WishlistManager::class)->create(auth()->user(), $data);
                if (Gate::allows('manageItems', $wishlist)) {
                    app(WishlistManager::class)->select(auth()->user(), $wishlist->id);
                }
                if (property_exists($this, 'selectedWishlistId')) {
                    $this->selectedWishlistId = $wishlist->id;
                    $this->listScope = 'mine';
                    $this->selectedArticles = [];
                    $this->search = '';
                    $this->resetPage();
                }
                Notification::make()->title(__('wishlists.created'))->success()->send();
                $this->refreshWishlistCards();
            });
    }

    public function toggleWishlistArticle(int $articleId): void
    {
        Gate::authorize('view', OrderArticle::findOrFail($articleId));
        $manager = app(WishlistManager::class);
        if (! $manager->target(auth()->user()) && Gate::denies('create', Wishlist::class)) {
            Notification::make()->title(__('wishlists.no_target'))->warning()->send();

            return;
        }
        $wishlist = $manager->target(auth()->user(), true);
        Gate::authorize('manageItems', $wishlist);
        $manager->select(auth()->user(), $wishlist->id);
        if ($wishlist->items()->where('order_article_id', $articleId)->exists()) {
            $this->notifyWishlistRemoval($manager->remove(auth()->user(), $wishlist, [$articleId]));
        } else {
            $manager->add(auth()->user(), $wishlist, $articleId);
            Notification::make()->title(__('wishlists.saved'))->body($wishlist->name)->success()->send();
        }
        $this->refreshWishlistCards();
    }

    public function removeWishlistArticle(int $wishlistId, int $articleId): void
    {
        $wishlist = Wishlist::findOrFail($wishlistId);
        $this->notifyWishlistRemoval(app(WishlistManager::class)->remove(auth()->user(), $wishlist, [$articleId]));
        if (property_exists($this, 'selectedArticles')) {
            $this->selectedArticles = array_values(array_filter($this->selectedArticles, fn (int|string $id): bool => (int) $id !== $articleId));
        }
        $this->refreshWishlistCards();
    }

    protected function notifyWishlistRemoval(string $token): void
    {
        Notification::make()->title(__('wishlists.removed'))->success()->duration(10000)
            ->actions([Action::make('undo')->label(__('wishlists.undo'))->button()->dispatch('wishlist-undo', ['token' => $token])->close()])->send();
    }

    #[On('wishlist-undo')]
    public function undoWishlistRemoval(string $token): void
    {
        app(WishlistManager::class)->undo(auth()->user(), $token);
        Notification::make()->title(__('wishlists.restored'))->success()->send();
        $this->refreshWishlistCards();
    }

    protected function refreshWishlistCards(): void
    {
        if ($this instanceof HasTable) {
            $this->flushCachedTableRecords();
        }
    }

    protected function wishlistSharingAction(): Action
    {
        return Action::make('shareWishlist')->label(__('wishlists.share'))->icon('heroicon-o-user-group')->color('gray')
            ->authorize(fn (): bool => $this->selectedWishlist() && Gate::allows('share', $this->selectedWishlist()))
            ->fillForm(function (): array {
                $wishlist = $this->selectedWishlist();
                Gate::authorize('share', $wishlist);

                return [
                    'users' => $wishlist->sharedUsers->whereNotIn('id', [auth()->id(), $wishlist->user_id])->map(fn (User $user): array => ['id' => $user->id, 'access' => $user->pivot->access])->values()->all(),
                    'departments' => $wishlist->sharedDepartments->map(fn (Department $department): array => ['id' => $department->id, 'access' => $department->pivot->access])->all(),
                ];
            })->schema([
                Repeater::make('users')->label(__('wishlists.users'))->schema([
                    Select::make('id')->label(__('wishlists.user'))
                        ->options(fn (): array => User::whereNotIn('id', [auth()->id(), $this->selectedWishlist()->user_id])->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()->required()->distinct(),
                    Select::make('access')->label(__('wishlists.access'))->options(['read' => __('wishlists.read'), 'edit' => __('wishlists.edit')])->required()->default('read'),
                ])->columns(2)->defaultItems(0),
                Repeater::make('departments')->label(__('general.departments'))->schema([
                    Select::make('id')->label(__('wishlists.department'))->options(fn (): array => Department::orderBy('name')->pluck('name', 'id')->all())->searchable()->required()->distinct(),
                    Select::make('access')->label(__('wishlists.access'))->options(['read' => __('wishlists.read'), 'edit' => __('wishlists.edit')])->required()->default('read'),
                ])->columns(2)->defaultItems(0),
            ])->action(function (array $data): void {
                app(WishlistManager::class)->share(auth()->user(), $this->selectedWishlist(), $data);
                Notification::make()->title(__('wishlists.sharing_saved'))->success()->send();
            });
    }
}
