# Projekt-Kontext: Logistics Portal
- Tech-Stack: Laravel 12, PHP 8.3+, FilamentPHP 4, Livewire.
- Fokus: Schreibe sauberen, wartbaren Code. Vermeide Erklärungen, gib mir direkt den Code.

# Coding-Standards
- Nutze strikte Typisierung (`declare(strict_types=1);`).
- Bevorzuge modernes PHP (z. B. Match-Expressions, Constructor Property Promotion, Nullsafe-Operatoren).
- Nutze Eloquent ORM für Datenbankabfragen, außer es ist sehr performancekritisch.
- Übersetzungen (`lang/de/general.php`) werden über die `__()` Funktion geladen.

# UI & Filament
- Nutze in Filament die statischen `make()` Methoden (z. B. `TextInput::make('name')`).
- Berücksichtige die in Filament üblichen Layout-Komponenten (Grid, Section, Fieldset).
