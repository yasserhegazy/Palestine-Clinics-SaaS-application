# Patient Profile API Documentation

This document outlines the API endpoints for managing patient profiles.

## Authentication

All endpoints require a valid Bearer Token in the Authorization header.
`Authorization: Bearer <your_token>`

---

## 1. Get Patient Profile

Retrieves the profile information of a specific patient.

-   **URL:** `/api/clinic/patients/{patient_id}`
-   **Method:** `GET`
-   **Role Required:** Secretary, Manager, Doctor
-   **Clinic Restriction:** Patient must belong to the same clinic as the authenticated user

### URL Parameters

| Parameter    | Type      | Description                       |
| :----------- | :-------- | :-------------------------------- |
| `patient_id` | `integer` | The ID of the patient to retrieve |

### Success Response (200 OK)

```json
{
    "patient_id": 1,
    "user_id": 7,
    "national_id": "893333644",
    "date_of_birth": "1989-03-20T00:00:00.000000Z",
    "gender": "Female",
    "address": "154 Kohler Haven Apt. 780\nBartonton, OR 49208",
    "blood_type": "B-",
    "allergies": null,
    "created_at": "2025-12-05T11:54:13.000000Z",
    "updated_at": "2025-12-05T11:54:13.000000Z",
    "user": {
        "user_id": 7,
        "clinic_id": 1,
        "name": "Patient One",
        "email": "patient1@clinic.com",
        "phone": "+970590000006",
        "role": "Patient",
        "status": "Active",
        "email_verified_at": "2025-12-05T11:54:13.000000Z",
        "created_at": "2025-12-05T11:54:13.000000Z",
        "updated_at": "2025-12-05T11:54:13.000000Z",
        "deleted_at": null
    }
}
```

### Error Responses

-   **403 Forbidden:**
    -   User is not associated with any clinic.
    -   Patient belongs to a different clinic.
-   **404 Not Found:** Patient ID does not exist.

---

## 2. Update Patient Profile

Updates the profile information of an existing patient.

-   **URL:** `/api/clinic/patients/{patient_id}`
-   **Method:** `PUT`
-   **Role Required:** Secretary, Manager
-   **Clinic Restriction:** Patient must belong to the same clinic as the authenticated user

### URL Parameters

| Parameter    | Type      | Description                     |
| :----------- | :-------- | :------------------------------ |
| `patient_id` | `integer` | The ID of the patient to update |

### Request Body

| Field          | Type     | Required | Description                                               |
| :------------- | :------- | :------- | :-------------------------------------------------------- |
| `patient_id`   | `integer`| Yes      | The ID of the patient (must match URL parameter)          |
| `name`         | `string` | Yes      | Patient's full name (max 100 characters)                  |
| `nationalId`   | `string` | Yes      | National ID (max 20 characters, must be unique)           |
| `phone`        | `string` | Yes      | Phone number (max 20 characters)                          |
| `dateOfBirth`  | `date`   | Yes      | Date of birth (format: YYYY-MM-DD, must be past or today) |
| `gender`       | `string` | Yes      | Gender (Must be: Male, Female, or Other)                  |
| `address`      | `string` | Yes      | Patient's address (max 255 characters)                    |
| `bloodType`    | `string` | No       | Blood type (max 5 characters, e.g., A+, O-, AB+)          |
| `allergies`    | `string` | No       | Known allergies or medical conditions                     |

### Example Request

```json
{
    "patient_id": 1,
    "name": "Ahmed Mohammed Ali",
    "nationalId": "401234567",
    "phone": "+970599123456",
    "dateOfBirth": "1989-03-20",
    "gender": "Male",
    "address": "123 Main Street, Gaza",
    "bloodType": "A+",
    "allergies": "Penicillin, Pollen"
}
```

### Success Response (200 OK)

```json
{
    "message": "Patient updated successfully",
    "patient": {
        "patient_id": 1,
        "user_id": 7,
        "national_id": "401234567",
        "date_of_birth": "1989-03-20",
        "gender": "Male",
        "address": "123 Main Street, Gaza",
        "blood_type": "A+",
        "allergies": "Penicillin, Pollen",
        "created_at": "2025-12-05T11:54:13.000000Z",
        "updated_at": "2025-12-07T14:30:00.000000Z",
        "user": {
            "user_id": 7,
            "clinic_id": 1,
            "name": "Ahmed Mohammed Ali",
            "email": "patient_401234567@clinic.local",
            "phone": "+970599123456",
            "role": "Patient",
            "status": "Active",
            "email_verified_at": "2025-12-05T11:54:13.000000Z",
            "created_at": "2025-12-05T11:54:13.000000Z",
            "updated_at": "2025-12-07T14:30:00.000000Z",
            "deleted_at": null
        }
    }
}
```

### Error Responses

-   **403 Forbidden:**
    -   User is not associated with any clinic.
    -   Patient belongs to a different clinic.
-   **404 Not Found:** Patient ID does not exist.
-   **422 Unprocessable Entity:**
    -   Phone number already registered by another patient.
    -   Validation errors (missing required fields, invalid format, etc.).

### Validation Rules

-   **name:** Required, string, maximum 100 characters
-   **nationalId:** Required, string, maximum 20 characters, must be unique (except for the current patient)
-   **phone:** Required, string, maximum 20 characters, must be unique across all users
-   **dateOfBirth:** Required, valid date, must be today or in the past
-   **gender:** Required, must be one of: Male, Female, Other
-   **address:** Required, string, maximum 255 characters
-   **bloodType:** Optional, string, maximum 5 characters
-   **allergies:** Optional, string, no length limit

### Notes

-   Phone numbers are automatically normalized to international format
-   Email is automatically generated based on national ID: `patient_{nationalId}@clinic.local`
-   Both the user account and patient profile are updated in a single transaction
-   If any error occurs during the update, all changes are rolled back

---

## Common Error Responses

### 401 Unauthorized

```json
{
    "message": "Unauthenticated"
}
```

Occurs when the Bearer token is missing or invalid.

### 403 Forbidden

```json
{
    "message": "You are not associated with any clinic"
}
```

Occurs when the authenticated user is not linked to a clinic.

```json
{
    "message": "You are not associated with this clinic"
}
```

Occurs when trying to access a patient from a different clinic.

### 404 Not Found

```json
{
    "message": "No query results for model [App\\Models\\Patient]"
}
```

Occurs when the specified patient ID does not exist.

### 422 Unprocessable Entity

```json
{
    "message": "Phone number already registered"
}
```

Occurs when the phone number is already used by another patient.

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "name": ["The name field is required."],
        "nationalId": ["The national id has already been taken."],
        "dateOfBirth": ["The date of birth must be a date before or equal to today."]
    }
}
```

Occurs when validation fails for one or more fields.

### 500 Internal Server Error

```json
{
    "message": "Failed to update patient",
    "error": "Error details here"
}
```

Occurs when an unexpected error happens during the update process.

---

## Usage Examples

### Example 1: Get Patient Profile

```bash
curl -X GET "http://your-domain.com/api/clinic/patients/1" \
  -H "Authorization: Bearer your_token_here" \
  -H "Accept: application/json"
```

### Example 2: Update Patient Profile

```bash
curl -X PUT "http://your-domain.com/api/clinic/patients/1" \
  -H "Authorization: Bearer your_token_here" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "patient_id": 1,
    "name": "Ahmed Mohammed Ali",
    "nationalId": "401234567",
    "phone": "+970599123456",
    "dateOfBirth": "1989-03-20",
    "gender": "Male",
    "address": "123 Main Street, Gaza",
    "bloodType": "A+",
    "allergies": "Penicillin, Pollen"
  }'
```

---

## Best Practices

1. **Always validate patient data on the client side** before sending to the API to reduce errors.
2. **Handle all error responses appropriately** and display user-friendly messages.
3. **Use the normalized phone format** (+970XXXXXXXXX) to ensure consistency.
4. **Keep national IDs secure** as they are sensitive personal information.
5. **Update patient information promptly** when changes occur to maintain accurate records.
6. **Check for duplicate phone numbers** before allowing users to proceed with updates.
7. **Provide clear feedback** to users when updates succeed or fail.

---

## Security Considerations

-   All endpoints require authentication via Bearer token
-   Users can only access patients within their own clinic
-   Phone numbers must be unique across the entire system
-   National IDs must be unique to prevent duplicate patient records
-   Patient data is protected by role-based access control (RBAC)
-   Database transactions ensure data integrity during updates
