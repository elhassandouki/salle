<?php

namespace App\Console\Commands;

use App\Models\Abonne;
use App\Models\Coach;
use App\Models\Paiement;
use App\Models\Pointage;
use App\Models\Service;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyBdTestData extends Command
{
    protected $signature = 'legacy:import-bd-test {--only=* : Sections to import (coaches,abonnes,services,subscriptions,paiements,pointages)}';

    protected $description = 'Import clean data from the legacy bd_test database into the new schema.';

    private array $abonneMaps = [
        'by_badgenumber' => [],
        'by_userid' => [],
    ];

    private array $coachMap = [];

    private array $serviceMap = [];

    public function handle(): int
    {
        $sections = $this->normalizeSections($this->option('only'));
        $legacy = DB::connection('legacy_mysql');

        try {
            $legacy->getPdo();
        } catch (\Throwable $e) {
            $this->error('Legacy DB connection failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info('Legacy import started from database: ' . $legacy->getDatabaseName());

        if ($this->shouldRun('coaches', $sections)) {
            $this->importCoaches($legacy);
        } else {
            $this->coachMap = $this->buildCoachMap();
        }

        if ($this->shouldRun('abonnes', $sections)) {
            $this->importAbonnes($legacy);
        } else {
            $this->abonneMaps = $this->buildAbonneMaps();
        }

        if ($this->shouldRun('services', $sections)) {
            $this->importServices($legacy);
        } else {
            $this->serviceMap = $this->buildServiceMap();
        }

        if ($this->shouldRun('subscriptions', $sections)) {
            $this->importSubscriptions($legacy);
        }

        if ($this->shouldRun('paiements', $sections)) {
            $this->importPaiements($legacy);
        }

        if ($this->shouldRun('pointages', $sections)) {
            $this->importPointages($legacy);
        }

        $this->info('Legacy import finished.');

        return self::SUCCESS;
    }

    private function importCoaches($legacy): void
    {
        $createdOrUpdated = 0;

        foreach ($legacy->table('coache')->orderBy('id')->get() as $row) {
            [$nom, $prenom] = $this->splitFullName((string) ($row->name ?? 'Coach legacy'));
            $email = $this->normalizeEmail($row->email ?? null);
            $cin = $this->normalizeString($row->cin ?? null);

            $query = Coach::query();
            if ($cin !== null) {
                $query->where('specialite', 'legacy_cin:' . $cin);
            } elseif ($email !== null) {
                $query->where('email', $email);
            } else {
                $query->where('nom', $nom)->where('prenom', $prenom);
            }

            $coach = $query->first() ?? new Coach();
            $coach->fill([
                'nom' => $nom,
                'prenom' => $prenom,
                'telephone' => $this->normalizeString($row->tel ?? null),
                'email' => $email,
                'specialite' => $cin !== null ? 'legacy_cin:' . $cin : ($coach->specialite ?? null),
                'date_embauche' => $this->normalizeDate($row->dated ?? null),
                'statut' => 'actif',
            ]);
            $coach->save();

            $this->coachMap[(string) $row->id] = $coach->id;
            $createdOrUpdated++;
        }

        $this->line("Coaches imported/updated: {$createdOrUpdated}");
    }

    private function importAbonnes($legacy): void
    {
        $createdOrUpdated = 0;
        $issueCards = $legacy->table('personnel_issuecard')->get()->keyBy('UserID_id');

        foreach ($legacy->table('userinfo')->orderBy('USERID')->get() as $row) {
            $badgeNumber = $this->normalizeIdentifier($row->Badgenumber ?? null);
            $legacyUserId = (string) $row->USERID;
            [$nom, $prenom] = $this->splitFullName((string) ($row->Name ?? ('Legacy User ' . $legacyUserId)));
            $issueCard = $issueCards->get($row->USERID);
            $cardId = $this->normalizeIdentifier($row->CardNo ?? null)
                ?? $this->normalizeIdentifier($issueCard->cardno ?? null);
            $cin = $this->normalizeIdentifier($row->identitycard ?? null)
                ?? $this->normalizeIdentifier($row->SSN ?? null);

            $query = Abonne::query();
            if ($badgeNumber !== null) {
                $query->where('uid', $badgeNumber);
            } else {
                $query->where('notes', 'like', '%legacy_user_id:' . $legacyUserId . '%');
            }

            $abonne = $query->first() ?? new Abonne();
            $existingNotes = (string) $abonne->notes;
            $noteParts = array_filter([
                $existingNotes !== '' ? $existingNotes : null,
                'legacy_user_id:' . $legacyUserId,
                $badgeNumber !== null ? 'legacy_badgenumber:' . $badgeNumber : null,
            ]);

            $abonne->fill([
                'uid' => $badgeNumber ?? $legacyUserId,
                'card_id' => $cardId,
                'nom' => $nom,
                'prenom' => $prenom,
                'cin' => $cin,
                'telephone' => $this->normalizeString($row->OPHONE ?? null) ?? $this->normalizeString($row->FPHONE ?? null),
                'email' => $this->normalizeEmail($row->email ?? null),
                'sexe' => $this->mapGender($row->Gender ?? null),
                'date_naissance' => $this->normalizeDate($row->BIRTHDAY ?? null),
                'lieu_naissance' => $this->normalizeString($row->birthplace ?? null),
                'nationalite' => $this->normalizeString($row->contry ?? null),
                'adresse' => $this->normalizeString($row->homeaddress ?? null) ?? $this->normalizeString($row->street ?? null),
                'notes' => implode(' | ', array_unique($noteParts)),
            ]);
            $abonne->save();

            if ($badgeNumber !== null) {
                $this->abonneMaps['by_badgenumber'][$badgeNumber] = $abonne->id;
            }
            $this->abonneMaps['by_userid'][$legacyUserId] = $abonne->id;
            $createdOrUpdated++;
        }

        $this->line("Abonnes imported/updated: {$createdOrUpdated}");
    }

    private function importServices($legacy): void
    {
        $createdOrUpdated = 0;
        $pricingBySport = [];

        foreach ($legacy->table('abonnement')->orderBy('id')->get() as $row) {
            $sportId = (string) $row->type_sport;
            $pricingBySport[$sportId] ??= [];
            $pricingBySport[$sportId][] = [
                'duree' => (int) ($row->duree ?? 0),
                'tarif' => (float) ($row->tarif ?? 0),
            ];
        }

        foreach ($legacy->table('type_sport')->orderBy('id')->get() as $row) {
            $service = Service::firstOrNew([
                'nom' => trim((string) $row->libelle),
                'type' => 'activite',
            ]);

            $prices = $this->buildPricesFromLegacyRows($pricingBySport[(string) $row->id] ?? []);
            $service->fill([
                'description' => $this->normalizeString($row->ref ?? null),
                'coach_id' => $this->coachMap[(string) $row->coache] ?? null,
                'prix_mensuel' => $prices['mensuel'],
                'prix_trimestriel' => $prices['trimestriel'],
                'prix_annuel' => $prices['annuel'],
                'statut' => ((int) ($row->status ?? 0) === 1) ? 'actif' : 'inactif',
            ]);
            $service->save();

            $this->serviceMap[(string) $row->id] = $service->id;
            $createdOrUpdated++;
        }

        $this->line("Services imported/updated: {$createdOrUpdated}");
    }

    private function importSubscriptions($legacy): void
    {
        $processed = 0;
        $skipped = 0;

        foreach ($legacy->table('reglement')->orderBy('id')->get() as $row) {
            $abonneId = $this->resolveAbonneId($row->userid ?? null);
            if ($abonneId === null) {
                $skipped++;
                continue;
            }

            $serviceId = $this->resolveServiceId($legacy, $row->type_sport ?? null);
            if ($serviceId === null) {
                $skipped++;
                continue;
            }

            $startDate = $this->normalizeDate($row->date ?? null) ?? now()->toDateString();
            $duration = max((int) ($row->duree ?? 30), 1);
            $endDate = Carbon::parse($startDate)->addDays($duration)->toDateString();
            $amount = (float) ($row->total ?? $row->montant ?? 0);
            $legacyNote = 'legacy_reglement_id:' . $row->id;

            Subscription::updateOrCreate(
                ['notes' => $legacyNote],
                [
                    'abonne_id' => $abonneId,
                    'service_id' => $serviceId,
                    'type_abonnement' => $this->mapSubscriptionType($row->type_abonnement ?? null, $duration),
                    'date_debut' => $startDate,
                    'date_fin' => $endDate,
                    'montant' => $amount,
                    'remise' => 0,
                    'montant_total' => $amount,
                    'montant_paye' => $amount,
                    'reste' => 0,
                    'statut' => Carbon::parse($endDate)->isPast() ? 'expire' : 'actif',
                    'auto_renew' => false,
                ]
            );

            $processed++;
        }

        $this->line("Subscriptions imported/updated: {$processed}, skipped: {$skipped}");
    }

    private function importPaiements($legacy): void
    {
        $processed = 0;
        $skipped = 0;

        foreach ($legacy->table('reglement')->orderBy('id')->get() as $row) {
            $subscription = Subscription::query()
                ->where('notes', 'legacy_reglement_id:' . $row->id)
                ->first();

            if (! $subscription) {
                $skipped++;
                continue;
            }

            Paiement::updateOrCreate(
                ['reference' => 'legacy-reglement-' . $row->id],
                [
                    'subscription_id' => $subscription->id,
                    'montant' => (float) ($row->total ?? $row->montant ?? 0),
                    'mode_paiement' => $this->mapPaymentMode($row->mode ?? null),
                    'date_paiement' => ($this->normalizeDate($row->date ?? null) ?? now()->toDateString()) . ' 00:00:00',
                    'notes' => 'legacy_n_reg:' . ($row->n_reg ?? $row->id),
                ]
            );

            $processed++;
        }

        $this->line("Paiements imported/updated: {$processed}, skipped: {$skipped}");
    }

    private function importPointages($legacy): void
    {
        $processed = 0;
        $skipped = 0;

        foreach ($legacy->table('checkinout')->orderBy('LOGID')->get() as $row) {
            $abonneId = $this->resolveAbonneId($row->USERID ?? null, true);
            if ($abonneId === null || empty($row->CHECKTIME)) {
                $skipped++;
                continue;
            }

            Pointage::updateOrCreate(
                [
                    'abonne_id' => $abonneId,
                    'uid' => (string) ($row->USERID ?? ''),
                    'date_pointage' => $row->CHECKTIME,
                    'type' => $this->mapPointageType($row->CHECKTYPE ?? null),
                ],
                [
                    'synced' => true,
                ]
            );

            $processed++;
        }

        $this->line("Pointages imported/updated: {$processed}, skipped: {$skipped}");
    }

    private function resolveAbonneId(mixed $legacyIdentifier, bool $preferLegacyUserId = false): ?int
    {
        $identifier = $this->normalizeString($legacyIdentifier);

        if ($identifier === null) {
            return null;
        }

        if ($preferLegacyUserId && isset($this->abonneMaps['by_userid'][$identifier])) {
            return $this->abonneMaps['by_userid'][$identifier];
        }

        if (isset($this->abonneMaps['by_badgenumber'][$identifier])) {
            return $this->abonneMaps['by_badgenumber'][$identifier];
        }

        return $this->abonneMaps['by_userid'][$identifier] ?? null;
    }

    private function resolveServiceId($legacy, mixed $legacySportId): ?int
    {
        $sportId = $this->normalizeString($legacySportId);
        if ($sportId === null) {
            return null;
        }

        if (isset($this->serviceMap[$sportId])) {
            return $this->serviceMap[$sportId];
        }

        $legacySport = $legacy->table('type_sport')->where('id', $sportId)->first();
        if (! $legacySport) {
            $service = Service::firstOrCreate(
                ['nom' => 'Service legacy ' . $sportId, 'type' => 'activite'],
                ['statut' => 'actif']
            );
            $this->serviceMap[$sportId] = $service->id;

            return $service->id;
        }

        $service = Service::firstOrCreate(
            ['nom' => trim((string) $legacySport->libelle), 'type' => 'activite'],
            ['statut' => ((int) ($legacySport->status ?? 0) === 1) ? 'actif' : 'inactif']
        );

        $this->serviceMap[$sportId] = $service->id;

        return $service->id;
    }

    private function buildCoachMap(): array
    {
        return Coach::query()
            ->get()
            ->mapWithKeys(fn (Coach $coach) => [$coach->id => $coach->id])
            ->all();
    }

    private function buildAbonneMaps(): array
    {
        $maps = [
            'by_badgenumber' => [],
            'by_userid' => [],
        ];

        foreach (Abonne::query()->get() as $abonne) {
            if ($abonne->uid) {
                $maps['by_badgenumber'][(string) $abonne->uid] = $abonne->id;
            }

            if (preg_match('/legacy_user_id:([^\|\s]+)/', (string) $abonne->notes, $matches)) {
                $maps['by_userid'][$matches[1]] = $abonne->id;
            }
        }

        return $maps;
    }

    private function buildServiceMap(): array
    {
        return [];
    }

    private function buildPricesFromLegacyRows(array $rows): array
    {
        $prices = [
            'mensuel' => 0,
            'trimestriel' => 0,
            'annuel' => 0,
        ];

        foreach ($rows as $row) {
            $bucket = $this->mapSubscriptionType(null, (int) ($row['duree'] ?? 0));
            $prices[$bucket] = max($prices[$bucket], (float) ($row['tarif'] ?? 0));
        }

        if ($prices['trimestriel'] === 0) {
            $prices['trimestriel'] = $prices['mensuel'] * 3;
        }

        if ($prices['annuel'] === 0) {
            $prices['annuel'] = $prices['mensuel'] * 12;
        }

        return $prices;
    }

    private function mapSubscriptionType(mixed $legacyType, int $duration): string
    {
        $legacyType = $this->normalizeString($legacyType);

        return match (true) {
            in_array($legacyType, ['1', '2'], true) => 'annuel',
            in_array($legacyType, ['6', '7', '8', '9', '10'], true) => 'trimestriel',
            $duration >= 300 => 'annuel',
            $duration >= 80 => 'trimestriel',
            default => 'mensuel',
        };
    }

    private function mapPaymentMode(mixed $mode): string
    {
        $mode = strtoupper($this->normalizeString($mode) ?? '');

        return match ($mode) {
            'ESP', 'ESPÈCES', 'ESPECES' => 'especes',
            'CB', 'CARTE' => 'carte',
            'CHQ', 'CHEQUE' => 'cheque',
            'VIR', 'VIREMENT' => 'virement',
            default => 'especes',
        };
    }

    private function mapPointageType(mixed $legacyType): string
    {
        $legacyType = strtoupper($this->normalizeString($legacyType) ?? '');

        return in_array($legacyType, ['O', 'OUT'], true) ? 'sortie' : 'entree';
    }

    private function mapGender(mixed $gender): ?string
    {
        $gender = strtoupper($this->normalizeString($gender) ?? '');

        return match ($gender) {
            'M', 'MALE' => 'Homme',
            'F', 'FEMALE' => 'Femme',
            default => null,
        };
    }

    private function normalizeSections(array $rawSections): array
    {
        $sections = [];

        foreach ($rawSections as $rawSection) {
            foreach (explode(',', (string) $rawSection) as $section) {
                $section = strtolower(trim($section));
                if ($section !== '') {
                    $sections[] = $section;
                }
            }
        }

        return array_values(array_unique($sections));
    }

    private function shouldRun(string $section, array $sections): bool
    {
        return $sections === [] || in_array($section, $sections, true);
    }

    private function splitFullName(string $fullName): array
    {
        $fullName = trim(preg_replace('/\s+/', ' ', $fullName) ?? '');

        if ($fullName === '') {
            return ['Legacy', 'User'];
        }

        $parts = explode(' ', $fullName);
        $nom = array_shift($parts) ?: 'Legacy';
        $prenom = trim(implode(' ', $parts));

        return [$nom, $prenom !== '' ? $prenom : 'User'];
    }

    private function normalizeString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeEmail(mixed $value): ?string
    {
        $email = $this->normalizeString($value);

        return $email !== null && filter_var($email, FILTER_VALIDATE_EMAIL) ? strtolower($email) : null;
    }

    private function normalizeIdentifier(mixed $value): ?string
    {
        $value = $this->normalizeString($value);

        if ($value === null) {
            return null;
        }

        return in_array($value, ['0', '00', '000', '0000', '00000'], true) ? null : $value;
    }

    private function normalizeDate(mixed $value): ?string
    {
        $value = $this->normalizeString($value);

        if ($value === null) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
