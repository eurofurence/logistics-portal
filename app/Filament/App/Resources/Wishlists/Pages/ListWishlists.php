<?php

namespace App\Filament\App\Resources\Wishlists\Pages;

use App\Actions\TableOrderAction;
use App\Filament\App\Resources\Wishlists\Concerns\InteractsWithWishlists;
use App\Filament\App\Resources\Wishlists\WishlistResource;
use App\Models\OrderArticle;
use App\Models\User;
use App\Models\Wishlist;
use App\Services\WishlistManager;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

class ListWishlists extends Page
{
    use InteractsWithWishlists;
    use WithPagination;

    protected static string $resource = WishlistResource::class;

    protected string $view = 'filament.app.wishlists.index';

    #[Url(as: 'list')]
    public ?int $selectedWishlistId = null;

    #[Url]
    public string $search = '';

    #[Url]
    public string $listScope = 'mine';

    /** @var list<int> */
    public array $selectedArticles = [];

    public function getTitle(): string
    {
        return __('wishlists.title');
    }

    public function mount(): void
    {
        Gate::authorize('viewAny', Wishlist::class);
        $this->validateOnly('listScope', ['listScope' => ['in:mine,shared,all']]);
        $this->selectedWishlistId ??= $this->wishlistTarget()?->id ?? app(WishlistManager::class)->lists(auth()->user())->first()?->id;
        $wishlist = $this->selectedWishlist();
        if ($wishlist && $wishlist->user_id !== auth()->id() && $this->listScope === 'mine') {
            $this->listScope = Gate::allows('manage-all-Wishlist') ? 'all' : 'shared';
        }
    }

    public function selectedWishlist(): ?Wishlist
    {
        if ($this->selectedWishlistId) {
            $wishlist = Wishlist::with('user')->findOrFail($this->selectedWishlistId);
            Gate::authorize('view', $wishlist);

            return $wishlist;
        }

        return null;
    }

    public function chooseWishlist(int $id): void
    {
        $wishlist = Wishlist::findOrFail($id);
        Gate::authorize('view', $wishlist);
        $this->selectedWishlistId = $id;
        $this->selectedArticles = [];
        $this->search = '';
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->selectedArticles = [];
        $this->resetPage();
    }

    public function updatedListScope(): void
    {
        $this->validateOnly('listScope', ['listScope' => ['in:mine,shared,all']]);
        $this->selectedWishlistId = $this->visibleLists()->first()?->id;
        $this->selectedArticles = [];
        $this->search = '';
        $this->resetPage();
    }

    public function updatedSelectedWishlistId(): void
    {
        $this->selectedWishlist();
        $this->selectedArticles = [];
        $this->resetPage();
    }

    public function removeSelected(): void
    {
        $this->validate(['selectedArticles' => ['required', 'array', 'max:100'], 'selectedArticles.*' => ['integer']]);
        abort_unless($wishlist = $this->selectedWishlist(), 404);
        $this->notifyWishlistRemoval(app(WishlistManager::class)->remove(auth()->user(), $wishlist, $this->selectedArticles));
        $this->selectedArticles = [];
    }

    public function setQuickTarget(): void
    {
        abort_unless($wishlist = $this->selectedWishlist(), 404);
        app(WishlistManager::class)->select(auth()->user(), $wishlist->id);
        Notification::make()->title(__('wishlists.target_saved'))->success()->send();
    }

    public function renameWishlistAction(): Action
    {
        return Action::make('renameWishlist')->label(__('wishlists.details'))->icon('heroicon-o-pencil')->color('gray')
            ->authorize(fn (): bool => $this->selectedWishlist() && Gate::allows('update', $this->selectedWishlist()))
            ->fillForm(fn (): array => $this->selectedWishlist()->only(['name', 'description']))
            ->schema([
                TextInput::make('name')->label(__('general.name'))->required()->maxLength(255),
                Textarea::make('description')->label(__('general.description'))->maxLength(10000),
            ])->action(function (array $data): void {
                $wishlist = $this->selectedWishlist();
                Gate::authorize('update', $wishlist);
                $wishlist->update($data);
            });
    }

    public function shareWishlistAction(): Action
    {
        return $this->wishlistSharingAction();
    }

    public function orderWishlistArticleAction(): Action
    {
        $canOrder = fn (?OrderArticle $record): bool => $record && ! $record->trashed()
            && Gate::allows('view', $record) && Gate::allows('order', $record);

        return TableOrderAction::make()->name('orderWishlistArticle')->button()->size('sm')
            ->modalHeading(fn (?OrderArticle $record): string => __('general.make_order').': '.($record?->name ?? ''))
            ->record(function (array $arguments): ?OrderArticle {
                if (! isset($arguments['article_id'])) {
                    return null;
                }

                return $this->selectedWishlist()?->items()->where('order_article_id', $arguments['article_id'])->first()?->article;
            })
            ->visible($canOrder)->authorize($canOrder);
    }

    public function transferOwnershipAction(): Action
    {
        return Action::make('transferOwnership')->label(__('wishlists.transfer_ownership'))->icon('heroicon-o-arrow-path')->color('gray')
            ->authorize(fn (): bool => $this->selectedWishlist() && Gate::allows('transferOwnership', $this->selectedWishlist()))
            ->requiresConfirmation()->modalHeading(__('wishlists.transfer_ownership'))
            ->modalDescription(__('wishlists.transfer_description'))->modalSubmitActionLabel(__('wishlists.transfer_confirm'))
            ->schema([
                Select::make('new_owner_id')->label(__('wishlists.new_owner'))->required()->searchable()
                    ->options(fn (): array => User::whereKeyNot($this->selectedWishlist()->user_id)->orderBy('name')->pluck('name', 'id')->all()),
            ])->action(function (array $data): void {
                $wishlist = app(WishlistManager::class)->transferOwnership(auth()->user(), $this->selectedWishlist(), (int) $data['new_owner_id']);
                $this->selectedWishlistId = Gate::allows('view', $wishlist) ? $wishlist->id : null;
                $this->selectedArticles = [];
                $this->search = '';
                if ($this->selectedWishlistId) {
                    $this->listScope = Gate::allows('manage-all-Wishlist') ? 'all' : 'shared';
                }
                $this->resetPage();
                Notification::make()->title(__('wishlists.ownership_transferred'))->success()->send();
            });
    }

    public function deleteWishlistAction(): Action
    {
        return Action::make('deleteWishlist')->label(__('wishlists.delete_list'))->icon('heroicon-o-trash')->color('danger')
            ->authorize(fn (): bool => $this->selectedWishlist() && Gate::allows('delete', $this->selectedWishlist()))
            ->requiresConfirmation()->modalDescription(__('wishlists.delete_confirmation'))
            ->action(function (): void {
                $wishlist = $this->selectedWishlist();
                Gate::authorize('delete', $wishlist);
                $wishlist->delete();
                $this->selectedWishlistId = null;
                $this->selectedArticles = [];
                $this->resetPage();
            });
    }

    protected function getHeaderActions(): array
    {
        return [$this->newWishlistAction()];
    }

    protected function getViewData(): array
    {
        $user = auth()->user();
        $lists = $this->visibleLists();
        $wishlist = $this->selectedWishlist();
        $itemsQuery = $wishlist?->items()->with('article')
            ->when($this->search !== '', fn (Builder $query): Builder => $query->whereHas('article', function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->where('name', 'like', '%'.$this->search.'%')->orWhere('article_number', 'like', '%'.$this->search.'%');
                });
            }))->latest('id');
        $items = $itemsQuery?->paginate(18);
        if ($items && $items->currentPage() > $items->lastPage()) {
            $this->setPage($items->lastPage());
            $items = $itemsQuery->paginate(18);
        }

        return [
            'lists' => $lists->get(), 'wishlist' => $wishlist, 'items' => $items,
            'editableIds' => app(WishlistManager::class)->lists($user, true)->pluck('wishlists.id')->all(),
            'canEdit' => $wishlist && Gate::allows('manageItems', $wishlist),
        ];
    }

    protected function visibleLists(): Builder
    {
        $user = auth()->user();
        $lists = app(WishlistManager::class)->lists($user);
        if ($this->listScope === 'all') {
            Gate::authorize('manage-all-Wishlist');
        } elseif ($this->listScope === 'shared') {
            $lists->where('user_id', '!=', $user->id);
        } else {
            $lists->where('user_id', $user->id);
        }

        return $lists;
    }
}
