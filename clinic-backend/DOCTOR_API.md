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

---

## 4. Reschedule Appointment

Reschedules an existing appointment to a new date and time. The appointment status is automatically set to "Approved".

-   **URL:** `/api/doctor/appointments/reschedule/{appointment_id}`
-   **Method:** `PUT`
-   **Role Required:** Doctor

### URL Parameters

| Parameter        | Type      | Description                             |
| :--------------- | :-------- | :-------------------------------------- |
| `appointment_id` | `integer` | The ID of the appointment to reschedule |

### Request Body

| Field              | Type     | Required | Description                                                       |
| :----------------- | :------- | :------- | :---------------------------------------------------------------- |
| `appointment_date` | `date`   | Yes      | New appointment date (format: YYYY-MM-DD, must be today or later) |
| `appointment_time` | `string` | Yes      | New appointment time (e.g., "10:00 AM")                           |

### Example Request

```json
{
    "appointment_date": "2025-12-05",
    "appointment_time": "02:00 PM"
}
```

### Success Response (200 OK)

```json
{
    "message": "Appointment rescheduled successfully",
    "appointment": {
        "appointment_id": 1,
        "status": "Approved",
        "appointment_date": "2025-12-05T14:00:00.000000Z",
        "appointment_time": "02:00 PM",
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

-   **403 Forbidden:**
    -   User is not a doctor.
    -   Appointment belongs to another doctor.
-   **404 Not Found:**
    -   Appointment ID does not exist.
    -   Doctor profile not found.
-   **422 Unprocessable Entity:**
    -   Invalid date format.
    -   Date is in the past.

---

## 5. Get Today's Appointments

Retrieves all approved appointments for the authenticated doctor scheduled for today.

-   **URL:** `/api/doctor/appointments/today`
-   **Method:** `GET`
-   **Role Required:** Doctor

### Success Response (200 OK)

```json
{
    "appointments": [
        {
            "appointment_id": 15,
            "clinic_id": 1,
            "doctor_id": 5,
            "patient_id": 10,
            "appointment_date": "2025-12-05",
            "appointment_time": "10:00 AM",
            "status": "Approved",
            "notes": "Regular checkup",
            "created_at": "2025-12-01T09:00:00.000000Z",
            "updated_at": "2025-12-01T09:00:00.000000Z",
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
                "name": "Main Health Clinic",
                "address": "123 Medical Street"
            }
        },
        {
            "appointment_id": 16,
            "clinic_id": 1,
            "doctor_id": 5,
            "patient_id": 12,
            "appointment_date": "2025-12-05",
            "appointment_time": "02:00 PM",
            "status": "Approved",
            "notes": "Follow-up visit",
            "patient": {
                "patient_id": 12,
                "user_id": 18,
                "user": {
                    "user_id": 18,
                    "name": "Jane Smith",
                    "email": "jane@example.com",
                    "phone": "+970599654321"
                }
            },
            "clinic": {
                "clinic_id": 1,
                "name": "Main Health Clinic",
                "address": "123 Medical Street"
            }
        }
    ],
    "total": 2,
    "date": "2025-12-05"
}
```

### Response Fields

| Field          | Type      | Description                            |
| :------------- | :-------- | :------------------------------------- |
| `appointments` | `array`   | List of today's approved appointments  |
| `total`        | `integer` | Total number of appointments for today |
| `date`         | `string`  | Current date (YYYY-MM-DD format)       |

### Error Responses

-   **403 Forbidden:** User is not a doctor.
-   **404 Not Found:** Doctor profile not found.

---

## 6. Complete Appointment

Completes an approved appointment and creates a medical record for the visit. This endpoint performs both actions in a single transaction to ensure data integrity.

-   **URL:** `/api/doctor/appointments/complete/{appointment_id}`
-   **Method:** `POST`
-   **Role Required:** Doctor

### URL Parameters

| Parameter        | Type      | Description                           |
| :--------------- | :-------- | :------------------------------------ |
| `appointment_id` | `integer` | The ID of the appointment to complete |

### Request Body

| Field          | Type     | Required | Description                                          |
| :------------- | :------- | :------- | :--------------------------------------------------- |
| `symptoms`     | `string` | Yes      | Patient's symptoms during the visit (max 1000 chars) |
| `diagnosis`    | `string` | Yes      | Doctor's diagnosis (max 1000 chars)                  |
| `prescription` | `string` | No       | Prescribed medications or treatment (max 1000 chars) |
| `next_visit`   | `date`   | No       | Recommended date for next visit (format: YYYY-MM-DD) |

### Example Request

```json
{
    "symptoms": "Persistent headache, dizziness, and fatigue for the past 3 days",
    "diagnosis": "Tension headache likely due to stress and lack of sleep",
    "prescription": "Ibuprofen 400mg twice daily for 5 days. Ensure adequate rest and hydration.",
    "next_visit": "2025-12-20"
}
```

### Success Response (201 Created)

```json
{
    "message": "Appointment completed successfully",
    "appointment": {
        "appointment_id": 15,
        "clinic_id": 1,
        "doctor_id": 5,
        "patient_id": 10,
        "appointment_date": "2025-12-05",
        "appointment_time": "10:00 AM",
        "status": "Completed",
        "notes": "Regular checkup",
        "created_at": "2025-12-01T09:00:00.000000Z",
        "updated_at": "2025-12-05T10:30:00.000000Z",
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
            "name": "Main Health Clinic",
            "address": "123 Medical Street"
        },
        "doctor": {
            "doctor_id": 5,
            "user_id": 8,
            "specialization": "General Practice",
            "user": {
                "user_id": 8,
                "name": "Dr. Sarah Johnson",
                "email": "sarah.johnson@clinic.com"
            }
        }
    },
    "medical_record": {
        "record_id": 42,
        "patient_id": 10,
        "doctor_id": 5,
        "clinic_id": 1,
        "visit_date": "2025-12-05 10:30:00",
        "symptoms": "Persistent headache, dizziness, and fatigue for the past 3 days",
        "diagnosis": "Tension headache likely due to stress and lack of sleep",
        "prescription": "Ibuprofen 400mg twice daily for 5 days. Ensure adequate rest and hydration.",
        "next_visit": "2025-12-20",
        "created_at": "2025-12-05T10:30:00.000000Z",
        "updated_at": "2025-12-05T10:30:00.000000Z"
    }
}
```

### Error Responses

-   **400 Bad Request:**
    -   Appointment is not in "Approved" status (e.g., already completed or cancelled).
    -   Response includes `current_status`.
    ```json
    {
        "message": "Only approved appointments can be completed",
        "current_status": "Completed"
    }
    ```
-   **403 Forbidden:**
    -   User is not a doctor.
    ```json
    {
        "message": "Only doctors can complete appointments"
    }
    ```
    -   Appointment belongs to another doctor.
    ```json
    {
        "message": "You do not have permission to complete this appointment"
    }
    ```
-   **404 Not Found:**
    -   Appointment ID does not exist.
    -   Doctor profile not found.
    ```json
    {
        "message": "Doctor profile not found"
    }
    ```
-   **422 Unprocessable Entity:**
    -   Missing required fields (`symptoms` or `diagnosis`).
    -   Invalid field values or formats.
-   **500 Internal Server Error:**
    -   Database transaction failed.
    ```json
    {
        "message": "Failed to complete appointment",
        "error": "Error details..."
    }
    ```

### Notes

-   This endpoint uses a database transaction to ensure both the appointment status update and medical record creation succeed or fail together.
-   The appointment status is automatically changed from "Approved" to "Completed".
-   The `visit_date` in the medical record is automatically set to the current timestamp.
-   The medical record is linked to the patient, doctor, and clinic from the appointment.
