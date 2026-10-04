<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\Native\GovernmentPharmacyImporter;
use App\Services\Native\GovernmentPharmacySpreadsheetReader;
use Illuminate\Console\Command;
use RuntimeException;

class ImportTaubateGovernmentPharmacies extends Command
{
    protected $signature = 'healthcare:import-taubate-pharmacies
                            {tenant : Slug do tenant que receberá os registros}';

    protected $description = 'Importa a planilha oficial do Programa Farmácia Popular para o tenant informado';

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
            $records = $reader->read(base_path('docs/09c1abc7-f600-4a36-8a94-170efe48c578.xlsx'));
            $summary = $importer->importTaubate($tenant, $records);
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
