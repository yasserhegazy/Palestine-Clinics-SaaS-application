<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PatientController extends Controller
{
    /**
     * Create a new patient and user account
     */
    public function createPatient(Request $request)
    {
        // Log incoming request data for debugging
        Log::info('Patient creation request', ['data' => $request->all()]);

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:100',
                'nationalId' => 'required|string|max:20|unique:patients,national_id',
                'phone' => 'required|string|max:20',
                'dateOfBirth' => 'required|date|before_or_equal:today',
                'gender' => 'required|in:Male,Female,Other',
                'address' => 'required|string|max:255',
                'bloodType' => 'nullable|string|max:5',
                'allergies' => 'nullable|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed', ['errors' => $e->errors()]);
            throw $e;
        }

        $user = $request->user();

        $phone = $this->normalizePhoneNumber($validated['phone']);

        // Ensure user (Secretary/Manager) has a clinic
        if (!$user->clinic_id) {
            return response()->json([
                'message' => 'You are not associated with any clinic',
            ], 403);
        }

        // Check if phone is already registered
        $existingUser = User::where('phone', $phone)->first();
        if ($existingUser) {
            return response()->json([
                'message' => 'Phone number already registered',
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Generate a random 8-character password
            $temporaryPassword = 12345678;

            // Generate email from national ID if not provided
            $email = 'patient_' . $validated['nationalId'] . '@clinic.local';

            // Create user account for patient
            $patientUser = User::create([
                'clinic_id' => $user->clinic_id,
                'name' => $validated['name'],
                'email' => $email,
                'phone' => $phone, // Use normalized phone
                'password_hash' => Hash::make($temporaryPassword),
                'role' => 'Patient',
                'status' => 'Active',
            ]);

            // Create patient profile
            $patient = Patient::create([
                'user_id' => $patientUser->user_id,
                'national_id' => $validated['nationalId'],
                'date_of_birth' => $validated['dateOfBirth'],
                'gender' => $validated['gender'],
                'address' => $validated['address'],
                'blood_type' => !empty($validated['bloodType']) ? $validated['bloodType'] : null,
                'allergies' => !empty($validated['allergies']) ? $validated['allergies'] : null,
            ]);

            DB::commit();

            // Load user relationship for full data
            $patient->load('user');

            // TODO: Send SMS with temporary password to phone number
            // This would integrate with an SMS service like Twilio
            // For now, we'll return it in the response

            return response()->json([
                'message' => 'Patient created successfully',
                'patient' => $patient,
                'user' => $patientUser,
                'temporary_password' => $temporaryPassword,
                'sms_sent' => false, // Will be true when SMS integration is added
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create patient',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all patients for the current clinic
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->clinic_id) {
            return response()->json([
                'message' => 'You are not associated with any clinic',
            ], 403);
        }

        $patients = Patient::with('user')
            ->whereHas('user', function ($query) use ($user) {
                $query->where('clinic_id', $user->clinic_id);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($patients);
    }

    /**
     * Get a specific patient
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        if (!$user->clinic_id) {
            return response()->json([
                'message' => 'You are not associated with any clinic',
            ], 403);
        }

        $patient = Patient::with(['user', 'appointments', 'medicalRecords'])
            ->whereHas('user', function ($query) use ($user) {
                $query->where('clinic_id', $user->clinic_id);
            })
            ->findOrFail($id);

        return response()->json($patient);
    }

    /**
     * Search for patients by national ID or phone
     */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'query' => 'required|string|min:3',
        ]);

        $user = $request->user();

        if (!$user->clinic_id) {
            return response()->json([
                'message' => 'You are not associated with any clinic',
            ], 403);
        }

        $query = $validated['query'];

        $patients = Patient::with('user')
            ->where(function ($q) use ($query) {
                $q->where('national_id', 'like', "%{$query}%");
            })
            ->orWhereHas('user', function ($q) use ($query, $user) {
                $q->where('clinic_id', $user->clinic_id)
                  ->where(function ($subQ) use ($query) {
                      $subQ->where('phone', 'like', "%{$query}%")
                           ->orWhere('name', 'like', "%{$query}%");
                  });
            })
            ->limit(10)
            ->get();

        return response()->json($patients);
    }

    /**
     * Search for patients using a single identifier field (national ID or phone prefix)
     */
    public function searchByIdentifier(Request $request)
    {
        $validated = $request->validate([
            'identifier' => 'required|string|min:3|max:25',
        ]);

        $user = $request->user();

        if (!$user->clinic_id) {
            return response()->json([
                'message' => 'You are not associated with any clinic',
            ], 403);
        }

        $identifier = trim($validated['identifier']);
        $phoneFragments = $this->buildPhoneSearchFragments($identifier);

        $patients = Patient::with('user')
            ->whereHas('user', function ($query) use ($user) {
                $query->where('clinic_id', $user->clinic_id);
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

        return response()->json([
            'patients' => $patients,
        ]);
    }

    /**
     * Normalize phone numbers to the +970 format
     */
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

    /**
     * Build phone fragments to allow loose matching for lookup
     */
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

        return array_values(array_unique(array_filter($fragments, function ($fragment) {
            return $fragment !== null && $fragment !== '';
        })));
    }
}
