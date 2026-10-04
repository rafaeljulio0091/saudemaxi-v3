<?php

namespace App\Services\Telemedicine;

use App\Services\Telemedicine\Concerns\MakesTelemedicineRequests;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;
use Throwable;

class LsxMedicalSchedulingClient
{
    use MakesTelemedicineRequests;

    /** @return list<array{id: int, name: string, price: ?float}> */
    public function specialties(): array
    {
        $result = $this->request(
            fn (PendingRequest $http) => $http->get(config('lsxmedical.scheduling.specialties_endpoint')),
            'scheduling.specialties',
        );

        return array_map(function (mixed $row): array {
            if (! is_array($row) || ! is_numeric($row['id'] ?? null) || ! is_string($row['name'] ?? null)) {
                $this->invalidResponse();
            }

            if (array_key_exists('price', $row) && $row['price'] !== null && ! is_numeric($row['price'])) {
                $this->invalidResponse();
            }

            return [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'price' => is_numeric($row['price'] ?? null) ? (float) $row['price'] : null,
            ];
        }, $this->list($result));
    }

    /** @return list<string> */
    public function businessDays(int $specialtyId): array
    {
        $result = $this->request(
            fn (PendingRequest $http) => $http->post(
                config('lsxmedical.scheduling.business_days_endpoint'),
                ['specialty_id' => $specialtyId],
            ),
            'scheduling.business-days',
        );

        return $this->formattedStringList($result, 'Y-m-d');
    }

    /** @return list<string> */
    public function availableTimes(int $specialtyId, string $date): array
    {
        $result = $this->request(
            fn (PendingRequest $http) => $http->post(
                config('lsxmedical.scheduling.available_times_endpoint'),
                ['specialty_id' => $specialtyId, 'date' => $date],
            ),
            'scheduling.available-times',
        );

        return $this->formattedStringList($result, 'H:i');
    }

    /** @return list<array{id: int, name: string, specialty: ?string, price: ?float, is_real: bool}> */
    public function doctors(int $specialtyId, string $date, string $time): array
    {
        $result = $this->request(
            fn (PendingRequest $http) => $http->post(
                config('lsxmedical.scheduling.doctors_endpoint'),
                ['specialty_id' => $specialtyId, 'date' => $date, 'time' => $time],
            ),
            'scheduling.doctors',
        );

        return array_map(function (mixed $row): array {
            if (
                ! is_array($row)
                || ! is_numeric($row['id'] ?? null)
                || ! is_string($row['name'] ?? null)
                || ! is_bool($row['is_real'] ?? null)
                || (array_key_exists('price', $row) && $row['price'] !== null && ! is_numeric($row['price']))
            ) {
                $this->invalidResponse();
            }

            return [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'specialty' => is_string($row['specialty'] ?? null) ? $row['specialty'] : null,
                'price' => is_numeric($row['price'] ?? null) ? (float) $row['price'] : null,
                'is_real' => (bool) ($row['is_real'] ?? false),
            ];
        }, $this->list($result));
    }

    /**
     * @param  array{patient_cpf: string, specialty_id: int, date: string, time: string, doctor_id: int, is_real_doctor: bool, is_paid: bool}  $payload
     * @return array{consultation_code: string, consultation_id: string, scheduled_for: string, is_paid: bool, price: ?float}
     */
    public function createConsultation(array $payload): array
    {
        $result = $this->request(
            fn (PendingRequest $http) => $http->post(
                config('lsxmedical.scheduling.create_consultation_endpoint'),
                $payload,
            ),
            'scheduling.create-consultation',
        );

        if (
            ($result['success'] ?? null) !== true
            || ! is_string($result['consultation_code'] ?? null)
            || trim($result['consultation_code']) === ''
            || (! is_int($result['consultation_id'] ?? null) && ! is_string($result['consultation_id'] ?? null))
            || trim((string) $result['consultation_id']) === ''
            || ! is_string($result['scheduled_for'] ?? null)
            || ! is_bool($result['is_paid'] ?? null)
            || (array_key_exists('price', $result) && $result['price'] !== null && ! is_numeric($result['price']))
        ) {
            $this->invalidResponse();
        }

        try {
            CarbonImmutable::parse($result['scheduled_for']);
        } catch (Throwable) {
            $this->invalidResponse();
        }

        return [
            'consultation_code' => $result['consultation_code'],
            'consultation_id' => (string) $result['consultation_id'],
            'scheduled_for' => $result['scheduled_for'],
            'is_paid' => (bool) $result['is_paid'],
            'price' => is_numeric($result['price'] ?? null) ? (float) $result['price'] : null,
        ];
    }

    /** @return list<mixed> */
    private function list(array $result): array
    {
        if (! array_is_list($result)) {
            $this->invalidResponse();
        }

        return $result;
    }

    /** @return list<string> */
    private function formattedStringList(array $result, string $format): array
    {
        $values = $this->list($result);

        foreach ($values as $value) {
            try {
                $parsed = is_string($value)
                    ? CarbonImmutable::createFromFormat('!'.$format, $value)
                    : false;
            } catch (Throwable) {
                $parsed = false;
            }

            if (! $parsed || $parsed->format($format) !== $value) {
                $this->invalidResponse();
            }
        }

        return $values;
    }

    private function invalidResponse(): never
    {
        throw new TelemedicineApiException(
            'O provedor de telemedicina retornou uma resposta inválida.',
            'invalid_response',
        );
    }
}
