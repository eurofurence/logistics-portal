<?php

namespace App\Services;

use Filament\Tables\Table;

class RecordActionContextMenu
{
    public static function apply(Table $table): Table
    {
        return $table->extraAttributes([
            'x-on:contextmenu' => e(<<<'JS'
                if ($event.target.closest('input, textarea, select, [contenteditable="true"], .fi-dropdown-panel')) return;
                const row = $event.target.closest('.fi-ta-row');
                const dropdown = row?.querySelector('.fi-ta-actions .fi-dropdown');
                if (!dropdown?.querySelector('.fi-dropdown-panel')) return;
                $event.preventDefault();
                $el.querySelectorAll('.fi-ta-actions .fi-dropdown').forEach((menu) => {
                    Alpine.$data(menu).close();
                });
                let anchor = dropdown.querySelector('[data-context-menu-anchor]');
                if (!anchor) {
                    anchor = document.createElement('span');
                    anchor.setAttribute('data-context-menu-anchor', '');
                    anchor.setAttribute('aria-hidden', 'true');
                    anchor.style.cssText = 'position: fixed; width: 0; height: 0; pointer-events: none;';
                    dropdown.appendChild(anchor);
                }
                anchor.style.left = `${$event.clientX}px`;
                anchor.style.top = `${$event.clientY}px`;
                Alpine.$data(dropdown).open(anchor);
                JS),
        ], merge: true);
    }
}
