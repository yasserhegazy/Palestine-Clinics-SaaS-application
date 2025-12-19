<?php

namespace App\Services\Admin;

use App\Models\Clinic;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AdminClinicService
{
    public function __construct(private readonly DatabaseManager $database)
    {
    }

    public function list(array $filters): LengthAwarePaginator
    {
        $query = Clinic::select('clinics.*')->withCount('users');

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $allowedSorts = ['created_at', 'name', 'status'];
        $sortBy = in_array($filters['sort_by'] ?? '', $allowedSorts, true) ? $filters['sort_by'] : 'created_at';
        $sortOrder = ($filters['sort_order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortOrder);

        $perPage = (int) ($filters['per_page'] ?? 15);

        return $query->paginate($perPage);
    }

    public function show(int $clinicId): Clinic
    {
        return Clinic::with([
            'users',
            'appointments',
        ])->findOrFail($clinicId);
    }

    public function update(int $clinicId, array $data): Clinic
    {
        $clinic = Clinic::findOrFail($clinicId);

        $clinic->update($data);

        return $clinic->fresh();
    }

    public function toggleStatus(int $clinicId): Clinic
    {
        $clinic = Clinic::findOrFail($clinicId);

        $clinic->status = $clinic->status === 'Active' ? 'Inactive' : 'Active';
        $clinic->save();

        return $clinic;
    }

    /**
     * @throws ValidationException
     */
    public function delete(int $clinicId): void
    {
        $clinic = Clinic::findOrFail($clinicId);

        if ($clinic->users()->exists()) {
            throw ValidationException::withMessages([
                'clinic' => ['Cannot delete clinic with existing users. Please deactivate instead.'],
            ]);
        }

        $clinic->delete();
    }

    public function updateLogo(Clinic $clinic, UploadedFile $logo): Clinic
    {
        $logoPath = $logo->store('clinics/logos', 'public');

        if ($clinic->logo_path) {
            Storage::disk('public')->delete($clinic->logo_path);
        }

        $clinic->logo_path = $logoPath;
        $clinic->save();

        return $clinic->fresh();
    }

    public function register(array $clinicData, array $managerData, ?UploadedFile $logo): array
    {
        $logoPath = null;

        return $this->database->transaction(function () use ($clinicData, $managerData, $logo, &$logoPath) {
            if ($logo) {
                $logoPath = $logo->store('clinics/logos', 'public');
                $clinicData['logo_path'] = $logoPath;
            }

            $clinic = Clinic::create($clinicData);

            /** @var User $manager */
            $manager = User::create([
                'clinic_id' => $clinic->clinic_id,
                'name' => $managerData['name'],
                'email' => $managerData['email'],
                'phone' => $managerData['phone'],
                'password_hash' => $managerData['password_hash'],
                'role' => 'Manager',
                'status' => 'Active',
            ]);

            $token = $manager->createToken('manager-token')->plainTextToken;

            return [
                'clinic' => $clinic->fresh(),
                'manager' => $manager,
                'token' => $token,
            ];
        });
    }
}
