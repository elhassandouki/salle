<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class AdminLteMenuBuilder
{
    public const MENU_SETTING_KEY = 'adminlte_menu';

    public static function build(): array
    {
        $databaseMenu = static::getMenuFromDatabase();

        if (! empty($databaseMenu)) {
            return $databaseMenu;
        }

        return static::getDefaultMenu();
    }

    public static function getDefaultMenu(): array
    {
        $menu = [];

        $menu[] = ['header' => 'PILOTAGE'];

        if (Route::has('dashboard.index')) {
            $menu[] = [
                'text' => 'Dashboard',
                'route' => 'dashboard.index',
                'icon' => 'fas fa-gauge-high',
            ];
        } elseif (Route::has('home')) {
            $menu[] = [
                'text' => 'Dashboard',
                'route' => 'home',
                'icon' => 'fas fa-gauge-high',
            ];
        }

        $menu[] = ['header' => 'GESTION QUOTIDIENNE'];

        $menu[] = static::routeItemIfTables(
            ['abonnes.index'],
            'Membres',
            'fas fa-users',
            ['abonnes']
        );

        $menu[] = static::routeItemIfTables(
            ['subscriptions.index', 'abonnements.index'],
            'Subscriptions',
            'fas fa-id-card',
            ['subscriptions'],
            static::badgeForTableCount('subscriptions', fn ($q) => $q->where('statut', 'actif'))
        );

        $menu[] = static::routeItemIfTables(
            ['paiements.index'],
            'Paiements',
            'fas fa-money-bill-wave',
            ['paiements'],
            static::badgeForTableCount('paiements', fn ($q) => $q->whereDate('date_paiement', today()), 'success')
        );

        $menu[] = static::routeItemIfTables(
            ['pointages.index'],
            'Pointages',
            'fas fa-door-open',
            ['pointages'],
            static::badgeForTableCount('pointages', fn ($q) => $q->whereDate('date_pointage', today()), 'info')
        );

        $serviceItems = array_values(array_filter([
            static::routeItemIfTables(['activites.index'], 'Activites', 'fas fa-dumbbell', ['services']),
            static::routeItemIfTables(['coaches.index'], 'Coachs', 'fas fa-chalkboard-teacher', ['coaches']),
            static::routeItem(['zk.status'], 'ZKTeco', 'fas fa-fingerprint'),
        ]));

        if ($serviceItems !== []) {
            $menu[] = [
                'text' => 'Services et controle',
                'icon' => 'fas fa-layer-group',
                'submenu' => $serviceItems,
            ];
        }

        $menu[] = ['header' => 'ASSURANCE'];

        $assuranceItems = array_values(array_filter([
            static::routeItemIfTables(['assurance_companies.index'], 'Compagnies', 'fas fa-building', ['services']),
            static::routeItemIfTables(['abonne_assurances.index'], 'Subscriptions assurance', 'fas fa-shield-heart', ['subscriptions']),
            static::routeItemIfTables(
                ['reclamation_assurances.index'],
                'Reclamations',
                'fas fa-file-medical',
                ['reclamations'],
                static::badgeForTableCount('reclamations', fn ($q) => $q->where('statut', 'en_attente'), 'warning')
            ),
        ]));

        if ($assuranceItems !== []) {
            $menu[] = [
                'text' => 'Assurance',
                'icon' => 'fas fa-shield-alt',
                'submenu' => $assuranceItems,
            ];
        }

        $menu[] = ['header' => 'ANALYSE'];

        $reportItems = array_values(array_filter([
            static::routeItem(['dashboard.rapports'], 'Vue generale', 'fas fa-chart-line'),
            static::routeItem(['rapports.financier'], 'Financier', 'fas fa-sack-dollar'),
            static::routeItem(['rapports.frequentation'], 'Frequentation', 'fas fa-people-group'),
            static::routeItem(['rapports.subscriptions'], 'Subscriptions', 'fas fa-chart-pie'),
            static::routeItem(['rapports.assurances'], 'Assurances', 'fas fa-notes-medical'),
        ]));

        if ($reportItems !== []) {
            $menu[] = [
                'text' => 'Rapports',
                'icon' => 'fas fa-chart-bar',
                'submenu' => $reportItems,
            ];
        }

        $menu[] = ['header' => 'SYSTEME'];

        $menu[] = static::routeItemIfTables(['settings.index'], 'Parametres', 'fas fa-cog', ['settings']);

        return array_values(array_filter($menu));
    }

    public static function getDefaultMenuJson(): string
    {
        return json_encode(static::getDefaultMenu(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
    }

    protected static function getMenuFromDatabase(): array
    {
        if (! static::canReadSettingsTable()) {
            return [];
        }

        $rawMenu = Setting::get(static::MENU_SETTING_KEY);

        if (! is_string($rawMenu) || trim($rawMenu) === '') {
            return [];
        }

        $decoded = json_decode($rawMenu, true);

        return is_array($decoded) ? $decoded : [];
    }

    protected static function canReadSettingsTable(): bool
    {
        try {
            return Schema::hasTable('settings');
        } catch (\Throwable) {
            return false;
        }
    }

    protected static function routeItem(array $routeNames, string $text, string $icon, array $extra = []): ?array
    {
        foreach ($routeNames as $routeName) {
            if (Route::has($routeName)) {
                return array_merge([
                    'text' => $text,
                    'route' => $routeName,
                    'icon' => $icon,
                ], $extra);
            }
        }

        return null;
    }

    protected static function routeItemIfTables(array $routeNames, string $text, string $icon, array $tables, array $extra = []): ?array
    {
        foreach ($tables as $table) {
            if (! static::hasTable($table)) {
                return null;
            }
        }

        return static::routeItem($routeNames, $text, $icon, $extra);
    }

    protected static function hasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }

    protected static function badgeForTableCount(string $table, ?callable $callback = null, string $color = 'primary'): array
    {
        if (! static::hasTable($table)) {
            return [];
        }

        try {
            $query = DB::table($table);

            if ($callback) {
                $callback($query);
            }

            $count = $query->count();

            if ($count < 1) {
                return [];
            }

            return [
                'label' => (string) $count,
                'label_color' => $color,
            ];
        } catch (\Throwable) {
            return [];
        }
    }
}
