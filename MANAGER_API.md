# Manager API Documentation

This document outlines the API endpoints for clinic managers to manage their clinic settings and staff.

## Authentication

All endpoints require a valid Bearer Token in the Authorization header.
`Authorization: Bearer <your_token>`

---

## 1. Get Clinic Settings

Retrieves the current clinic settings for the authenticated manager.

-   **URL:** `/api/manager/clinic/settings`
-   **Method:** `GET`
-   **Role Required:** Manager

### Success Response (200 OK)

```json
{
    "success": true,
    "clinic": {
        "clinic_id": 1,
        "name": "Main Health Clinic",
        "address": "123 Medical Street, Gaza",
        "phone": "+970599123456",
        "email": "info@mainhealthclinic.com",
        "logo_path": "clinic-logos/abc123.png",
        "logo_url": "http://localhost:8000/storage/clinic-logos/abc123.png",
        "subscription_plan": "Premium",
        "status": "Active",
        "created_at": "2025-11-15T10:00:00.000000Z",
        "updated_at": "2025-12-05T09:00:00.000000Z"
    }
}
```

### Error Responses

-   **403 Forbidden:** User is not a manager.
    ```json
    {
        "success": false,
        "message": "Only managers can view clinic settings"
    }
    ```
-   **404 Not Found:** Clinic not found.
    ```json
    {
        "success": false,
        "message": "Clinic not found"
    }
    ```

---

## 2. Update Clinic Settings

Updates the clinic settings for the authenticated manager's clinic. Supports updating clinic information and uploading a new logo.

-   **URL:** `/api/manager/clinic/settings`
-   **Method:** `PUT`
-   **Role Required:** Manager
-   **Content-Type:** `multipart/form-data` (when uploading logo) or `application/json`

### Request Body

| Field               | Type     | Required | Description                                          |
| :------------------ | :------- | :------- | :--------------------------------------------------- |
| `name`              | `string` | No       | Clinic name (max 100 chars)                          |
| `address`           | `string` | No       | Clinic address (max 255 chars)                       |
| `phone`             | `string` | No       | Clinic phone number (max 20 chars)                   |
| `email`             | `string` | No       | Clinic email (must be unique, max 100 chars)         |
| `subscription_plan` | `string` | No       | Subscription plan: `Basic`, `Standard`, or `Premium` |
| `status`            | `string` | No       | Clinic status: `Active` or `Inactive`                |
| `logo`              | `file`   | No       | Logo image (jpeg, jpg, png, gif, svg, max 2MB)       |

### Example Request (JSON)

```json
{
    "name": "Advanced Medical Center",
    "address": "456 Healthcare Avenue, Gaza",
    "phone": "+970599654321",
    "email": "contact@advancedmedical.com",
    "subscription_plan": "Premium",
    "status": "Active"
}
```

### Example Request (Form Data with Logo)

```
POST /api/manager/clinic/settings
Content-Type: multipart/form-data

name=Advanced Medical Center
address=456 Healthcare Avenue, Gaza
phone=+970599654321
email=contact@advancedmedical.com
subscription_plan=Premium
status=Active
logo=<binary file data>
```

### Success Response (200 OK)

```json
{
    "success": true,
    "message": "Clinic settings updated successfully",
    "clinic": {
        "clinic_id": 1,
        "name": "Advanced Medical Center",
        "address": "456 Healthcare Avenue, Gaza",
        "phone": "+970599654321",
        "email": "contact@advancedmedical.com",
        "logo_path": "clinic-logos/xyz789.png",
        "logo_url": "http://localhost:8000/storage/clinic-logos/xyz789.png",
        "subscription_plan": "Premium",
        "status": "Active",
        "created_at": "2025-11-15T10:00:00.000000Z",
        "updated_at": "2025-12-05T09:15:00.000000Z"
    }
}
```

### Error Responses

-   **403 Forbidden:** User is not a manager.
    ```json
    {
        "success": false,
        "message": "Only managers can update clinic settings"
    }
    ```
-   **404 Not Found:** Clinic not found.
    ```json
    {
        "success": false,
        "message": "Clinic not found"
    }
    ```
-   **422 Unprocessable Entity:** Validation failed.
    ```json
    {
        "success": false,
        "message": "Validation failed",
        "errors": {
            "email": ["The email has already been taken."],
            "logo": [
                "The logo must be an image.",
                "The logo must not be greater than 2048 kilobytes."
            ],
            "subscription_plan": ["The selected subscription plan is invalid."]
        }
    }
    ```

### Notes

-   All fields are optional. Only provide the fields you want to update.
-   When uploading a new logo, the old logo file will be automatically deleted from storage.
-   The logo is stored in the `storage/clinic-logos` directory.
-   Supported logo formats: JPEG, JPG, PNG, GIF, SVG (max 2MB).
-   The `logo_url` field in the response provides the full URL to access the logo.
-   Email must be unique across all clinics.
