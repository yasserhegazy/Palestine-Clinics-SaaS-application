# Medical History API Documentation

## Overview

This document details the API endpoints for fetching patient medical history (previous visits).

## Changelog

**2025-12-01**: Updated response format to include `data` wrapper and `count` field for consistency across all endpoints.

## Endpoints

### 1. Get Patient Medical History (Clinic Staff)

Fetches the medical history for a specific patient. Accessible by Managers and Secretaries.

-   **URL**: `/api/clinic/patients/{id}/history`
-   **Method**: `GET`
-   **Auth Required**: Yes (Role: Manager, Secretary, Doctor)
-   **URL Parameters**:
    -   `id` (integer): The ID of the patient.

#### Success Response

-   **Code**: 200 OK
-   **Content**:

```json
{
    "data": [
        {
            "date": "2025-02-28",
            "clinic": "Dermatology Clinic",
            "diagnosis": "Chronic skin allergy",
            "doctor": "Dr. Hazem Rabee"
        },
        {
            "date": "2025-01-15",
            "clinic": "Ophthalmology",
            "diagnosis": "Mild myopia",
            "doctor": "Dr. Sanaa Shahada"
        }
    ],
    "count": 2
}
```

#### Error Responses

-   **403 Forbidden**: User is not associated with a clinic.
-   **404 Not Found**: Patient not found or does not belong to the user's clinic.

---

### 2. Get My Medical History (Patient)

Fetches the medical history for the currently authenticated patient.

-   **URL**: `/api/patient/medical-history`
-   **Method**: `GET`
-   **Auth Required**: Yes (Role: Patient)

#### Success Response

-   **Code**: 200 OK
-   **Content**:

```json
{
    "data": [
        {
            "date": "2025-02-28",
            "clinic": "Dermatology Clinic",
            "diagnosis": "Chronic skin allergy",
            "doctor": "Dr. Hazem Rabee"
        }
    ],
    "count": 1
}
```

#### Error Responses

-   **403 Forbidden**: User is not a patient.
-   **404 Not Found**: Patient record not found for the user.
