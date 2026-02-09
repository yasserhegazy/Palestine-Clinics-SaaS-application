# Palestine Clinics SaaS Application - Backend

A SaaS-based clinic management system designed specifically for healthcare facilities in Palestine. This Laravel-based backend provides a complete API for managing multiple clinics, patients, doctors, appointments, and medical records.

## 🎯 Project Overview

This is a **multi-tenant SaaS platform** that enables healthcare clinics to digitize their operations. Each clinic operates independently with its own staff, patients, and data, while the platform administrator manages all clinics from a central dashboard.

### Key Features

-   **Multi-Clinic Management** - Platform admin can manage multiple independent clinics
-   **Role-Based Access Control** - 5 distinct user roles with specific permissions
-   **Appointment System** - Request, approve, and manage patient appointments
-   **Medical Records** - Doctors can create and manage patient medical histories
-   **Staff Management** - Add, update, and remove clinic staff (doctors & secretaries)
-   **Patient Management** - Register and track patient information
-   **Secure Authentication** - Laravel Sanctum token-based authentication

## 👥 User Roles

| Role               | Description                 | Access Level                                  |
| :----------------- | :-------------------------- | :-------------------------------------------- |
| **Platform Admin** | SaaS platform administrator | Manages all clinics, system-wide settings     |
| **Clinic Manager** | Clinic owner/administrator  | Manages their clinic's staff and operations   |
| **Doctor**         | Medical practitioner        | Manages appointments, creates medical records |
| **Secretary**      | Clinic receptionist         | Manages patients, schedules appointments      |
| **Patient**        | Clinic patient              | Views appointments and medical records        |

## 🏗️ System Architecture

### Database Structure

-   **Users** - Centralized user authentication with soft deletes
-   **Clinics** - Multi-tenant clinic information
-   **Doctors** - Doctor profiles with specializations
-   **Patients** - Patient demographics and medical information
-   **Appointments** - Appointment scheduling and tracking
-   **Medical Records** - Patient visit history and prescriptions

### Security Features

-   ✅ Token-based authentication (Laravel Sanctum)
-   ✅ Role-based authorization middleware
-   ✅ Clinic-level data isolation
-   ✅ Soft deletes for data preservation
-   ✅ Input validation and sanitization

## 📡 API Endpoints

### Authentication

-   `POST /api/auth/login` - User login
-   `POST /api/auth/logout` - User logout
-   `POST /api/register/clinic` - New clinic registration

### Doctor Endpoints

-   `GET /api/doctor/appointments` - List appointment requests
-   `PUT /api/doctor/appointments/approve/{id}` - Approve appointment
-   `GET /api/doctor/medical-records` - List medical records
-   `POST /api/doctor/medical-records` - Create medical record
-   `PUT /api/doctor/medical-records/{id}` - Update medical record
-   `DELETE /api/doctor/medical-records/{id}` - Delete medical record

### Patient Endpoints

-   `GET /api/patient/appointments` - View appointments
-   `POST /api/patient/appointments` - Request appointment
-   `GET /api/patient/medical-records` - View medical history
-   `GET /api/patient/doctors` - List available doctors

### Manager Endpoints

-   `POST /api/clinic/doctors` - Add doctor
-   `POST /api/clinic/secretaries` - Add secretary
-   `GET /api/clinic/staff` - List all staff
-   `PUT /api/clinic/staff/{id}` - Update staff member
-   `DELETE /api/clinic/staff/{id}` - Remove staff member
-   `POST /api/clinic/patients` - Register patient
-   `GET /api/clinic/patients` - List patients

## 🚀 Getting Started

### Prerequisites

-   PHP 8.1+
-   MySQL 8.0+
-   Composer
-   Laravel 11

### Installation

1. **Clone the repository**

```bash
git clone https://github.com/yasserhegazy/Palestine-Clinics-SaaS-application.git
cd Palestine-Clinics-SaaS-application/Backend/clinic-backend
```

2. **Install dependencies**

```bash
composer install
```

3. **Configure environment**

```bash
cp .env.example .env
php artisan key:generate
```

4. **Configure database** in `.env`

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=clinic_saas
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

5. **Run migrations and seeders**

```bash
php artisan migrate:fresh --seed
```

6. **Start the server**

```bash
php artisan serve
```

The API will be available at `http://127.0.0.1:8000`

### Default Credentials (Seeded Data)

| Role           | Email                 | Password     |
| :------------- | :-------------------- | :----------- |
| Platform Admin | admin@platform.com    | password     |
| Clinic Manager | manager@clinic.com    | password     |
| Doctor         | doctor1@clinic.com    | password     |
| Secretary      | secretary1@clinic.com | password     |
| Patient        | patient1@clinic.com   | password     |

## 📚 API Documentation

Detailed API documentation is available in the following files:

-   [Doctor API Documentation](DOCTOR_API.md)
-   [Staff Management API Documentation](STAFF_API.md)

## 🛠️ Technology Stack

-   **Framework:** Laravel 11
-   **Authentication:** Laravel Sanctum
-   **Database:** MySQL
-   **PHP Version:** 8.1+
-   **Architecture:** RESTful API

## 📝 Development Notes

### Soft Deletes

The system uses soft deletes for user records to preserve historical data and maintain referential integrity.

### Data Isolation

Each clinic's data is completely isolated. Users can only access data from their assigned clinic.

### Pagination

List endpoints return paginated results (10 items per page) for optimal performance.

## 🤝 Contributing

This is a private project for Palestine healthcare clinics. For any questions or contributions, please contact the project maintainer.

## 📄 License

This project is proprietary software developed for Palestine healthcare facilities.

## 👨‍💻 Author

**Yasser Hegazy**

-   GitHub: [@yasserhegazy](https://github.com/yasserhegazy)

---

**Made with ❤️ for Palestine Healthcare** 🇵🇸
