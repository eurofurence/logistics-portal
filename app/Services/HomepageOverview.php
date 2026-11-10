<?php

namespace App\Services;

use App\Filament\App\Resources\Bills\BillResource;
use App\Filament\App\Resources\Items\ItemResource;
use App\Filament\App\Resources\Orders\OrderResource;
use App\Filament\App\Resources\Storages\StorageResource;
use App\Filament\App\Resources\Wishlists\WishlistResource;
use App\Settings\GeneralSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class HomepageOverview
{
    /**
     * @return array<string, array{title: string, description: string, icon: string, links: list<array{label: string, url: string}>, count: int|null, records: list<array{title: string, department: string|null, status: string|null, url: string}>}>
     */
    public function sections(string $tab): array
    {
        if (! in_array($tab, ['overview', 'guide'], true)) {
            return [];
        }

        $settings = app(GeneralSettings::class);
        if (! $settings->{'homepage_'.$tab.'_enabled'}) {
            return [];
        }

        $sections = [];
        foreach ($this->areas() as $area => $configuration) {
            if (! $settings->{'homepage_'.$tab.'_'.$area}) {
                continue;
            }

            $resources = array_values(array_filter($configuration['resources'], fn (string $resource): bool => $resource::canViewAny()));
            if ($resources === []) {
                continue;
            }

            $links = [];
            foreach ($resources as $resource) {
                $links[] = ['label' => $resource::getNavigationLabel(), 'url' => $resource::getUrl('index', panel: 'app')];
                if ($resource::canCreate()) {
                    $links[] = ['label' => __('homepage.create', ['area' => $resource::getModelLabel()]), 'url' => $resource::getUrl('create', panel: 'app')];
                }
            }

            $section = [
                'title' => __('homepage.'.$area),
                'description' => filled($settings->{'homepage_'.$area.'_description'})
                    ? $settings->{'homepage_'.$area.'_description'} : __('homepage.'.$area.'_description'),
                'icon' => $configuration['icon'],
                'links' => $links,
                'count' => null,
                'records' => [],
            ];

            if ($tab === 'overview' && $area !== 'inventory') {
                $resource = $resources[0];
                $query = $this->visibleQuery($area);
                $section['count'] = (clone $query)->count();
                $section['records'] = $query->orderByDesc('updated_at')->orderByDesc('id')->limit(5)->get()
                    ->map(fn (Model $record): array => [
                        'title' => $area === 'bills' ? $record->title : $record->name,
                        'department' => match ($area) {
                            'orders' => $record->department?->name,
                            'bills' => $record->connected_department?->name,
                            default => null,
                        },
                        'status' => $area === 'wishlists' ? null : __('general.'.$record->status),
                        'url' => $area === 'wishlists'
                            ? $resource::getUrl('index', ['list' => $record->id], panel: 'app')
                            : $resource::getUrl('view', ['record' => $record], panel: 'app'),
                    ])->all();
            }

            $sections[$area] = $section;
        }

        return $sections;
    }

    private function visibleQuery(string $area): Builder
    {
        $user = auth()->user();

        return match ($area) {
            'orders' => OrderResource::getEloquentQuery()
                ->when(! $user->isSuperAdmin(), fn (Builder $query): Builder => $query
                    ->whereHas('department')->whereHas('event')
                    ->when(! $user->can('can-always-see-order'), fn (Builder $query): Builder => $query->where('status', '!=', 'locked'))),
            'bills' => BillResource::getEloquentQuery()
                ->with('connected_department')
                ->whereIn('status', ['open', 'processing', 'on_hold', 'checking']),
            'wishlists' => WishlistResource::getEloquentQuery(),
        };
    }

    /** @return array<string, array{icon: string, resources: list<class-string<\Filament\Resources\Resource>>}> */
    private function areas(): array
    {
        return [
            'orders' => ['icon' => 'heroicon-o-shopping-cart', 'resources' => [OrderResource::class]],
            'bills' => ['icon' => 'heroicon-o-banknotes', 'resources' => [BillResource::class]],
            'wishlists' => ['icon' => 'heroicon-o-rectangle-stack', 'resources' => [WishlistResource::class]],
            'inventory' => ['icon' => 'heroicon-o-building-storefront', 'resources' => [ItemResource::class, StorageResource::class]],
        ];
    }
}
