# Staff Management API Documentation

This document outlines the API endpoints for managing clinic staff (Secretaries and Doctors) as a Clinic Manager.

## Authentication

All endpoints require a valid Bearer Token in the Authorization header.
`Authorization: Bearer <your_token>`

---

## 1. List All Staff

Retrieves a list of all staff members (Doctors and Secretaries) in the clinic.

-   **URL:** `/api/clinic/staff`
-   **Method:** `GET`
-   **Role Required:** Manager

### Success Response (200 OK)

```json
{
    "members": [
        {
            "user_id": 10,
            "clinic_id": 1,
            "name": "Dr. Sarah Smith",
            "email": "sarah@example.com",
            "phone": "+970599123456",
            "role": "Doctor",
            "status": "Active",
            "created_at": "2025-11-20T09:00:00.000000Z",
            "clinic": {
                "clinic_id": 1,
                "name": "Main Health Clinic"
            }
        },
        {
            "user_id": 11,
            "clinic_id": 1,
            "name": "Jane Doe",
            "email": "jane@example.com",
            "phone": "+970599654321",
            "role": "Secretary",
            "status": "Active",
            "created_at": "2025-11-21T09:00:00.000000Z",
            "clinic": {
                "clinic_id": 1,
                "name": "Main Health Clinic"
            }
        }
    ]
}
```

---

## 2. Add Secretary

Adds a new secretary to the clinic.

-   **URL:** `/api/clinic/secretaries`
-   **Method:** `POST`
-   **Role Required:** Manager

### Request Body

| Field   | Type     | Required | Description                          |
| :------ | :------- | :------- | :----------------------------------- |
| `name`  | `string` | Yes      | Full name (max 100 chars)            |
| `email` | `string` | Yes      | Valid email address (must be unique) |
| `phone` | `string` | Yes      | Phone number                         |

### Success Response (201 Created)

```json
{
    "message": "Secretary added successfully",
    "secretary": {
        "user_id": 12,
        "name": "New Secretary",
        "email": "new@example.com",
        "role": "Secretary"
        // ... other fields
    },
    "temporary_password": "..."
}
```

---

## 3. Add Doctor

Adds a new doctor to the clinic.

-   **URL:** `/api/clinic/doctors`
-   **Method:** `POST`
-   **Role Required:** Manager

### Request Body

| Field            | Type     | Required | Description                               |
| :--------------- | :------- | :------- | :---------------------------------------- |
| `name`           | `string` | Yes      | Full name (max 100 chars)                 |
| `email`          | `string` | Yes      | Valid email address (must be unique)      |
| `phone`          | `string` | Yes      | Phone number                              |
| `specialization` | `string` | Yes      | Medical specialization (e.g., Cardiology) |
| `available_days` | `string` | Yes      | Working days (e.g., "Mon, Wed, Fri")      |
| `clinic_room`    | `string` | Yes      | Room number/name                          |

### Success Response (201 Created)

```json
{
    "message": "Doctor added successfully",
    "doctor": {
        "doctor_id": 5,
        "user_id": 13,
        "specialization": "Cardiology"
        // ... other fields
    },
    "user": {
        "user_id": 13,
        "name": "Dr. New"
        // ... other fields
    },
    "temporary_password": "..."
}
```

---

## 4. Update Staff Member

Updates information for an existing staff member.

-   **URL:** `/api/clinic/staff/{user_id}`
-   **Method:** `PUT`
-   **Role Required:** Manager

### Request Body

| Field            | Type     | Required | Description                          |
| :--------------- | :------- | :------- | :----------------------------------- |
| `name`           | `string` | Yes      | Full name                            |
| `email`          | `string` | Yes      | Valid email address                  |
| `phone`          | `string` | Yes      | Phone number                         |
| `specialization` | `string` | No       | (Doctor only) Medical specialization |
| `available_days` | `string` | No       | (Doctor only) Working days           |
| `clinic_room`    | `string` | No       | (Doctor only) Room number            |

### Success Response (200 OK)

```json
{
    "message": "Staff member updated successfully",
    "user": {
        "user_id": 10,
        "name": "Updated Name"
        // ... other fields
    }
}
```

---

## 5. Delete Staff Member

Soft deletes a staff member from the clinic.

-   **URL:** `/api/clinic/staff/{user_id}`
-   **Method:** `DELETE`
-   **Role Required:** Manager

### Success Response (200 OK)

```json
{
    "message": "Staff member deleted successfully"
}
```

### Error Responses

-   **403 Forbidden:**
    -   Cannot delete a Manager account.
    -   Cannot delete your own account.
-   **400 Bad Request:**
    -   Doctor has active appointments (must cancel/reassign first).
    -   Secretary has created appointments (cannot delete).
