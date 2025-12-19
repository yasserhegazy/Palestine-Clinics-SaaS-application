<?php

namespace App\Services\Clinic;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class StaffService
{
    private const DEFAULT_PASSWORD = '12345678';

    public function __construct(private readonly DatabaseManager $database)
    {
    }

    /**
     * @return array{secretary: User, temporary_password: string}
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function addSecretary(User $actor, array $data): array
    {
        $this->ensureActorHasClinic($actor);
        $this->assertPhoneUnique($data['phone']);

        $secretary = User::create([
            'clinic_id' => $actor->clinic_id,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $this->normalizePhone($data['phone']),
            'password_hash' => Hash::make(self::DEFAULT_PASSWORD),
            'role' => 'Secretary',
            'status' => 'Active',
        ]);

        return [
            'secretary' => $secretary,
            'temporary_password' => self::DEFAULT_PASSWORD,
        ];
    }

    /**
     * @return array{doctor: Doctor, user: User, temporary_password: string}
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function addDoctor(User $actor, array $data): array
    {
        $this->ensureActorHasClinic($actor);
        $this->assertPhoneUnique($data['phone']);

        return $this->database->transaction(function () use ($actor, $data) {
            $doctorUser = User::create([
                'clinic_id' => $actor->clinic_id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $this->normalizePhone($data['phone']),
                'password_hash' => Hash::make(self::DEFAULT_PASSWORD),
                'role' => 'Doctor',
                'status' => 'Active',
            ]);

            $doctor = Doctor::create([
                'user_id' => $doctorUser->user_id,
                'specialization' => $data['specialization'],
                'available_days' => $data['available_days'],
                'clinic_room' => $data['clinic_room'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'slot_duration' => $data['slot_duration'],
            ]);

            $doctor->load('user');

            return [
                'doctor' => $doctor,
                'user' => $doctorUser,
                'temporary_password' => self::DEFAULT_PASSWORD,
            ];
        });
    }

    /**
     * @throws AuthorizationException
     */
    public function list(User $actor): Collection
    {
        $this->ensureActorHasClinic($actor);

        return User::where('clinic_id', $actor->clinic_id)
            ->whereNotIn('role', ['Manager', 'Patient'])
            ->with(['clinic', 'doctor'])
            ->get();
    }

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function update(User $actor, int $userId, array $data): User
    {
        $this->ensureActorHasClinic($actor);

        $member = User::with('doctor')->findOrFail($userId);

        if ($member->clinic_id !== $actor->clinic_id) {
            throw new AuthorizationException('You do not have permission to update this member.');
        }

        $this->assertPhoneUnique($data['phone'], $member->user_id);

        return $this->database->transaction(function () use ($member, $data) {
            $member->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $this->normalizePhone($data['phone']),
            ]);

            if ($member->role === 'Doctor' && $member->doctor) {
                $doctorData = array_filter([
                    'specialization' => $data['specialization'] ?? null,
                    'available_days' => $data['available_days'] ?? null,
                    'clinic_room' => $data['clinic_room'] ?? null,
                    'start_time' => $data['start_time'] ?? null,
                    'end_time' => $data['end_time'] ?? null,
                    'slot_duration' => $data['slot_duration'] ?? null,
                ], static fn ($value) => $value !== null);

                if (!empty($doctorData)) {
                    $member->doctor->update($doctorData);
                }
            }

            return $member->fresh(['doctor']);
        });
    }

    /**
     * @throws AuthorizationException
     */
    public function delete(User $actor, int $userId): void
    {
        $this->ensureActorHasClinic($actor);

        $member = User::findOrFail($userId);

        if ($member->clinic_id !== $actor->clinic_id) {
            throw new AuthorizationException('You do not have permission to delete this member.');
        }

        if ($member->role === 'Manager') {
            throw new AuthorizationException('Cannot delete a manager account.');
        }

        if ($member->user_id === $actor->user_id) {
            throw new AuthorizationException('You cannot delete your own account.');
        }

        $member->delete();
    }

    private function ensureActorHasClinic(User $actor): void
    {
        if (!$actor->clinic_id) {
            throw new AuthorizationException('You are not associated with any clinic.');
        }
    }

    private function normalizePhone(string $phone): string
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

    private function assertPhoneUnique(string $phone, ?int $ignoreUserId = null): void
    {
        $query = User::where('phone', $this->normalizePhone($phone));

        if ($ignoreUserId) {
            $query->where('user_id', '!=', $ignoreUserId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'phone' => ['Phone number already registered.'],
            ]);
        }
    }
}
