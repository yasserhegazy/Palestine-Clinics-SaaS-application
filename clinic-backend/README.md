# Palestine Clinics SaaS - Backend

This directory contains the Laravel-based backend API for the Palestine Clinics SaaS application.

## 📁 Project Structure

```
Backend/
└── clinic-backend/     # Main Laravel application
    ├── app/
    │   ├── Http/
    │   │   └── Controllers/
    │   │       ├── Admin/          # Platform admin controllers
    │   │       ├── Auth/           # Authentication controllers
    │   │       ├── Clinic/         # Clinic manager controllers
    │   │       ├── Doctor/         # Doctor controllers
    │   │       └── Patient/        # Patient controllers
    │   └── Models/                 # Eloquent models
    ├── database/
    │   ├── migrations/             # Database migrations
    │   └── seeders/                # Database seeders
    ├── routes/
    │   └── api.php                 # API routes
    └── README.md                   # Detailed documentation
```

## 🚀 Quick Start

Navigate to the `clinic-backend` directory for full documentation:

```bash
cd clinic-backend
```

See [clinic-backend/README.md](clinic-backend/README.md) for:

- Installation instructions
- API documentation
- Database setup
- Development guidelines

## 🔑 Key Technologies

- **Framework:** Laravel 11
- **Authentication:** Laravel Sanctum
- **Database:** MySQL 8.0+
- **PHP:** 8.1+

## 📚 API Documentation

- [Doctor API](clinic-backend/DOCTOR_API.md)
- [Staff Management API](clinic-backend/STAFF_API.md)

## 🏥 Main Features

- Multi-tenant clinic management
- Role-based access control (5 roles)
- Appointment scheduling system
- Medical records management
- Staff and patient management
- Secure authentication with Sanctum

---

For detailed setup and usage instructions, please refer to the [clinic-backend README](clinic-backend/README.md).
