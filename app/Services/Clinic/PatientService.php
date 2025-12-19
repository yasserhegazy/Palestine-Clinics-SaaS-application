<?php

namespace App\Services\Clinic;

use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PatientService
{
    public function __construct(private readonly DatabaseManager $database)
    {
    }

    /**
     * Create a patient and related user.
     *
     * @return array{patient: Patient, temporary_password: string}
     *
     * @throws AuthorizationException
     */
    public function create(User $actor, array $data): array
    {
        $this->ensureActorHasClinic($actor);

        $normalizedPhone = $this->normalizePhoneNumber($data['phone']);
        $this->assertPhoneIsUnique($normalizedPhone);

        $temporaryPassword = "password";

        $patient = $this->database->transaction(function () use ($actor, $data, $normalizedPhone, $temporaryPassword) {
            $patientUser = User::create([
                'clinic_id' => $actor->clinic_id,
                'name' => $data['name'],
                'email' => $this->generateEmailFromNationalId($data['national_id']),
                'phone' => $normalizedPhone,
                'password_hash' => Hash::make($temporaryPassword),
                'role' => 'Patient',
                'status' => 'Active',
            ]);

            return Patient::create([
                'user_id' => $patientUser->user_id,
                'national_id' => $data['national_id'],
                'date_of_birth' => $data['date_of_birth'],
                'gender' => $data['gender'],
                'address' => $data['address'],
                'blood_type' => $data['blood_type'] ?? null,
                'allergies' => $data['allergies'] ?? null,
            ]);
        });

        $patient->load('user');

        return [
            'patient' => $patient,
            'temporary_password' => $temporaryPassword,
        ];
    }

    /**
     * Paginate patients scoped to the actor's clinic.
     *
     * @throws AuthorizationException
     */
    public function paginate(User $actor, int $perPage = 20): LengthAwarePaginator
    {
        $this->ensureActorHasClinic($actor);

        return Patient::with('user')
            ->whereHas('user', static function ($query) use ($actor) {
                $query->where('clinic_id', $actor->clinic_id);
            })
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Show a patient scoped to the clinic.
     *
     * @throws AuthorizationException
     */
    public function show(User $actor, int $patientId): Patient
    {
        $this->ensureActorHasClinic($actor);

        return Patient::with('user')
            ->whereHas('user', static function ($query) use ($actor) {
                $query->where('clinic_id', $actor->clinic_id);
            })
            ->findOrFail($patientId);
    }

    /**
     * Update patient and user information.
     *
     * @return Patient
     *
     * @throws AuthorizationException
     */
    public function update(User $actor, int $patientId, array $data): Patient
    {
        $this->ensureActorHasClinic($actor);

        $patient = Patient::with('user')->findOrFail($patientId);

        if (!$patient->user || $patient->user->clinic_id !== $actor->clinic_id) {
            throw new AuthorizationException('You are not associated with this clinic.');
        }

        $normalizedPhone = $this->normalizePhoneNumber($data['phone']);
        $this->assertPhoneIsUnique($normalizedPhone, $patient->user_id);

        $this->database->transaction(function () use ($data, $patient, $normalizedPhone) {
            $patient->user->update([
                'name' => $data['name'],
                'phone' => $normalizedPhone,
                'email' => $this->generateEmailFromNationalId($data['national_id']),
            ]);

            $patient->update([
                'national_id' => $data['national_id'],
                'date_of_birth' => $data['date_of_birth'],
                'gender' => $data['gender'],
                'address' => $data['address'],
                'blood_type' => $data['blood_type'] ?? null,
                'allergies' => $data['allergies'] ?? null,
            ]);
        });

        return $patient->fresh(['user']);
    }

    /**
     * Search patients by query.
     *
     * @throws AuthorizationException
     */
    public function search(User $actor, string $query): Collection
    {
        $this->ensureActorHasClinic($actor);

        return Patient::with('user')
            ->whereHas('user', static function ($q) use ($actor) {
                $q->where('clinic_id', $actor->clinic_id);
            })
            ->where(function ($q) use ($query, $actor) {
                $q->where('national_id', 'like', "%{$query}%");
                $q->orWhereHas('user', function ($userQuery) use ($query, $actor) {
                    $userQuery->where('clinic_id', $actor->clinic_id)
                        ->where(function ($subQ) use ($query) {
                            $subQ->where('phone', 'like', "%{$query}%")
                                ->orWhere('name', 'like', "%{$query}%");
                        });
                });
            })
            ->limit(10)
            ->get();
    }

    /**
     * Search patients by identifier (national ID or phone).
     *
     * @throws AuthorizationException
     */
    public function searchByIdentifier(User $actor, string $identifier): Collection
    {
        $this->ensureActorHasClinic($actor);

        $phoneFragments = $this->buildPhoneSearchFragments($identifier);

        return Patient::with('user')
            ->whereHas('user', static function ($query) use ($actor) {
                $query->where('clinic_id', $actor->clinic_id);
            })
            ->where(function ($query) use ($identifier, $phoneFragments) {
                $query->where('national_id', 'like', "{$identifier}%")
                    ->orWhereHas('user', function ($userQuery) use ($identifier, $phoneFragments) {
                        $userQuery->where(function ($phoneMatch) use ($identifier, $phoneFragments) {
                            $phoneMatch->where('phone', 'like', "{$identifier}%");
                            foreach ($phoneFragments as $fragment) {
                                $phoneMatch->orWhere('phone', 'like', "{$fragment}%");
                            }
                        });
                    });
            })
            ->orderBy('patients.created_at', 'desc')
            ->limit(5)
            ->get();
    }

    /**
     * Get patient medical history scoped to clinic.
     *
     * @return array{patient: Patient, medical_records: EloquentCollection<MedicalRecord>}
     *
     * @throws AuthorizationException
     */
    public function history(User $actor, int $patientId): array
    {
        $this->ensureActorHasClinic($actor);

        $patient = Patient::where('patient_id', $patientId)
            ->whereHas('user', static function ($query) use ($actor) {
                $query->where('clinic_id', $actor->clinic_id);
            })
            ->with('user')
            ->firstOrFail();

        $medicalRecords = MedicalRecord::where('patient_id', $patientId)
            ->with(['doctor.user', 'patient.user.clinic'])
            ->orderByDesc('visit_date')
            ->get();

        return [
            'patient' => $patient,
            'medical_records' => $medicalRecords,
        ];
    }

    private function ensureActorHasClinic(User $actor): void
    {
        if (!$actor->clinic_id) {
            throw new AuthorizationException('You are not associated with any clinic.');
        }
    }

    private function normalizePhoneNumber(string $phone): string
    {
        $phone = trim($phone);

        if ($phone === '') {
            return $phone;
        }

        if (str_starts_with($phone, '0')) {
            return '+970' . substr($phone, 1);
        }

        if (!str_starts_with($phone, '+')) {
            return '+970' . $phone;
        }

        return $phone;
    }

    private function buildPhoneSearchFragments(string $identifier): array
    {
        $fragments = [];
        $trimmed = trim($identifier);

        if ($trimmed !== '') {
            $fragments[] = $trimmed;
        }

        $normalized = $this->normalizePhoneNumber($trimmed);
        if ($normalized !== '' && $normalized !== $trimmed) {
            $fragments[] = $normalized;
        }

        $digitsOnly = preg_replace('/\D+/', '', $trimmed);
        if (!empty($digitsOnly)) {
            $fragments[] = $digitsOnly;

            if (str_starts_with($digitsOnly, '970')) {
                $fragments[] = '+' . $digitsOnly;
                $fragments[] = '0' . substr($digitsOnly, 3);
            } elseif (str_starts_with($digitsOnly, '0')) {
                $fragments[] = '+970' . substr($digitsOnly, 1);
            } else {
                $fragments[] = '+970' . $digitsOnly;
                $fragments[] = '0' . $digitsOnly;
            }
        }

        return array_values(array_unique(array_filter($fragments, static function ($fragment) {
            return $fragment !== null && $fragment !== '';
        })));
    }

    private function assertPhoneIsUnique(string $phone, ?int $ignoreUserId = null): void
    {
        $query = User::where('phone', $phone);

        if ($ignoreUserId !== null) {
            $query->where('user_id', '!=', $ignoreUserId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'phone' => ['Phone number already registered.'],
            ]);
        }
    }

    private function generateEmailFromNationalId(string $nationalId): string
    {
        return 'patient_' . $nationalId . '@clinic.local';
    }
}
