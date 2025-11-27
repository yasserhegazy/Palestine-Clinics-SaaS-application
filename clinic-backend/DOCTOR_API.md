# Doctor Appointment API Documentation

This document outlines the API endpoints for managing appointment requests as a Doctor.

## Authentication

All endpoints require a valid Bearer Token in the Authorization header.
`Authorization: Bearer <your_token>`

---

## 1. Get Appointment Requests

Retrieves a list of pending appointment requests for the authenticated doctor.

-   **URL:** `/api/doctor/appointments`
-   **Method:** `GET`
-   **Role Required:** Doctor

### Success Response (200 OK)

```json
{
    "appointments": [
        {
            "appointment_id": 1,
            "clinic_id": 1,
            "doctor_id": 5,
            "patient_id": 10,
            "appointment_date": "2025-11-30T10:00:00.000000Z",
            "status": "Requested",
            "notes": "Patient reported headache",
            "created_at": "2025-11-20T09:00:00.000000Z",
            "updated_at": "2025-11-20T09:00:00.000000Z",
            "patient": {
                "patient_id": 10,
                "user_id": 15,
                "user": {
                    "user_id": 15,
                    "name": "John Doe",
                    "email": "john@example.com",
                    "phone": "+970599123456"
                }
            },
            "clinic": {
                "clinic_id": 1,
                "name": "Main Health Clinic"
            }
        }
    ]
}
```

### Error Responses

-   **403 Forbidden:** User is not a doctor.
-   **404 Not Found:** Doctor profile not found.

---

## 2. Approve Appointment Request

Approves a specific appointment request.

-   **URL:** `/api/doctor/appointments/approve/{appointment_id}`
-   **Method:** `PUT`
-   **Role Required:** Doctor

### URL Parameters

| Parameter        | Type      | Description                          |
| :--------------- | :-------- | :----------------------------------- |
| `appointment_id` | `integer` | The ID of the appointment to approve |

### Success Response (200 OK)

```json
{
    "message": "Appointment request approved successfully",
    "appointment": {
        "appointment_id": 1,
        "status": "Approved",
        "appointment_date": "2025-11-30T10:00:00.000000Z",
        "patient": {
            "user": {
                "name": "John Doe"
            }
        },
        "clinic": {
            "name": "Main Health Clinic"
        }
        // ... other appointment fields
    }
}
```

### Error Responses

-   **400 Bad Request:**
    -   Appointment is not in "Requested" status (e.g., already approved).
    -   Response includes `current_status`.
-   **403 Forbidden:**
    -   User is not a doctor.
    -   Appointment belongs to another doctor.
-   **404 Not Found:** Appointment ID does not exist.

---

## 3. Reject Appointment Request

Rejects a specific appointment request with a reason.

-   **URL:** `/api/doctor/appointments/reject/{appointment_id}`
-   **Method:** `PUT`
-   **Role Required:** Doctor

### URL Parameters

| Parameter        | Type      | Description                         |
| :--------------- | :-------- | :---------------------------------- |
| `appointment_id` | `integer` | The ID of the appointment to reject |

### Request Body

| Field              | Type     | Required | Description                          |
| :----------------- | :------- | :------- | :----------------------------------- |
| `rejection_reason` | `string` | Yes      | Reason for rejection (max 500 chars) |

### Example Request

```json
{
    "rejection_reason": "Doctor is on vacation during the requested date"
}
```

### Success Response (200 OK)

```json
{
    "message": "Appointment request rejected successfully",
    "appointment": {
        "appointment_id": 1,
        "status": "Cancelled",
        "rejection_reason": "Doctor is on vacation during the requested date",
        "appointment_date": "2025-11-30T10:00:00.000000Z",
        "patient": {
            "user": {
                "name": "John Doe"
            }
        },
        "clinic": {
            "name": "Main Health Clinic"
        }
        // ... other appointment fields
    }
}
```

### Error Responses

-   **400 Bad Request:**
    -   Appointment is not in "Requested" status.
    -   Response includes `current_status`.
    -   Missing or invalid `rejection_reason`.
-   **403 Forbidden:**
    -   User is not a doctor.
    -   Appointment belongs to another doctor.
-   **404 Not Found:** Appointment ID does not exist.
