<?php

namespace App\Filament\Pages;

use App\Services\HomepageOverview;
use App\Settings\GeneralSettings;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected static string $routePath = 'start';

    public function content(Schema $schema): Schema
    {
        $settings = app(GeneralSettings::class);
        $tabs = [];
        $tabContents = [];
        foreach (['overview', 'guide'] as $tab) {
            $sections = app(HomepageOverview::class)->sections($tab);
            if ($sections === []) {
                continue;
            }

            $cards = [];
            foreach ($sections as $area => $section) {
                $cards[] = Section::make($section['title'])
                    ->icon($section['icon'])
                    ->schema([
                        View::make('filament.app.homepage.card')->viewData(['section' => $section, 'area' => $area, 'isGuide' => $tab === 'guide']),
                    ]);
            }

            $components = [Grid::make(['default' => 1, 'lg' => 2])->schema($cards)];
            if ($tab === 'guide') {
                $components[] = View::make('filament.app.homepage.workflow')->viewData(['sections' => $sections]);
            }
            $tabs[] = Tab::make(__('homepage.'.$tab))
                ->icon($tab === 'overview' ? Heroicon::OutlinedSquares2x2 : Heroicon::OutlinedBookOpen)
                ->id($tab)
                ->schema($components);
            $tabContents[] = $components;
        }

        return $schema->components([
            View::make('filament.app.homepage.welcome')->viewData([
                'title' => filled($settings->homepage_title) ? $settings->homepage_title : __('homepage.welcome'),
                'intro' => filled($settings->homepage_intro) ? $settings->homepage_intro : __('homepage.intro'),
                'name' => auth()->user()->name,
                'hasContent' => $tabs !== [],
            ]),
            ...match (count($tabs)) {
                0 => [],
                1 => $tabContents[0],
                default => [Tabs::make('homepage')->tabs($tabs)->columnSpanFull()],
            },
        ])->columns(1);
    }

    public static function getNavigationLabel(): string
    {
        return __('general.start');
    }

    public function getTitle(): string|Htmlable
    {
        return __('general.start');
    }
}
