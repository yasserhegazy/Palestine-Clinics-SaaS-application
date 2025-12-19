<?php

namespace App\Services\Manager;

use App\Models\Clinic;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ManagerClinicService
{
    /**
     * @throws AuthorizationException
     */
    public function getClinic(User $manager): Clinic
    {
        return $this->resolveClinic($manager);
    }

    /**
     * @throws AuthorizationException
     */
    public function update(User $manager, array $data, ?UploadedFile $logo): Clinic
    {
        $clinic = $this->resolveClinic($manager);

        if (!empty($data)) {
            $clinic->update($data);
        }

        if ($logo) {
            if ($clinic->logo_path) {
                Storage::disk('public')->delete($clinic->logo_path);
            }

            $clinic->logo_path = $logo->store('clinic-logos', 'public');
            $clinic->save();
        }

        return $clinic->fresh();
    }

    /**
     * @throws AuthorizationException
     */
    public function logo(User $manager): array
    {
        $clinic = $this->resolveClinic($manager);

        return [
            'logo_path' => $clinic->logo_path,
            'logo_url' => $clinic->logo_path ? url('storage/' . $clinic->logo_path) : null,
        ];
    }

    /**
     * @throws AuthorizationException
     */
    private function resolveClinic(User $manager): Clinic
    {
        if ($manager->role !== 'Manager' || !$manager->clinic_id) {
            throw new AuthorizationException('Only managers with an associated clinic can perform this action.');
        }

        return Clinic::findOrFail($manager->clinic_id);
    }
}
