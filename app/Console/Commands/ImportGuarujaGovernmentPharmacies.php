<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Native\GovernmentPharmacyImporter;
use App\Services\Native\GovernmentPharmacySpreadsheetReader;
use Illuminate\Console\Command;
use RuntimeException;

class ImportGuarujaGovernmentPharmacies extends Command
{
    protected $signature = 'healthcare:import-guaruja-pharmacies
                            {tenant : Slug do tenant que receberá os registros}';

    protected $description = 'Importa as farmácias oficiais de Guarujá para o tenant informado';

    public function handle(
        GovernmentPharmacySpreadsheetReader $reader,
        GovernmentPharmacyImporter $importer,
    ): int {
        $tenant = Tenant::query()->where('slug', $this->argument('tenant'))->first();

        if (! $tenant) {
            $this->error('Tenant não encontrado.');

            return self::FAILURE;
        }

        try {
            $records = $reader->read(base_path('docs/25432b7a-f5d8-441e-bf08-9d14e2a6dc76.xlsx'));
            $summary = $importer->import(
                $tenant,
                'Guarujá',
                'SP',
                '3518701',
                $records,
                ['Guaruja'],
            );
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->table(['Criadas', 'Atualizadas', 'Sem alteração'], [[
            $summary['created'],
            $summary['updated'],
            $summary['unchanged'],
        ]]);

        return self::SUCCESS;
    }
}
