<?php

namespace App\Triage\Actions;

use App\Models\TriageSession;
use App\Models\User;
use App\Triage\Enums\TriageStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class StartTriageSession
{
    public function execute(User $patient): TriageSession
    {
        if (! $patient->isPatient() || ! $patient->tenant_id) {
            abort(403, 'A triagem está disponível somente para pacientes vinculados a um cliente.');
        }

        $lock = Cache::lock("triage:start:{$patient->id}", 10);

        if (! $lock->get()) {
            throw new ConflictHttpException('Já existe uma solicitação de triagem em andamento.');
        }

        try {
            return DB::transaction(function () use ($patient) {
                $active = TriageSession::query()
                    ->where('tenant_id', $patient->tenant_id)
                    ->where('patient_id', $patient->id)
                    ->where('status', TriageStatus::Active)
                    ->latest('started_at')
                    ->first();

                return $active ?? TriageSession::create([
                    'tenant_id' => $patient->tenant_id,
                    'patient_id' => $patient->id,
                    'status' => TriageStatus::Active,
                    'started_at' => now(),
                ]);
            });
        } finally {
            $lock->release();
        }
    }
}
