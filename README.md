# 🐾 PetNest - Pet Care & Boarding Web Application

**Course:** SENG 21253 - Web Application Development  
**Group:** 13

PetNest is a role-based full-stack web application that connects pet owners with verified local pet keepers for boarding and pet care.

---

## 🚀 Tech Stack
- **Frontend:** HTML5, CSS3 (Earthy/Warm Brown Theme), JavaScript (Vanilla JS / Form Validation)
- **Backend:** PHP 8.x (PDO Prepared Statements, Session Management, RBAC)
- **Database:** MySQL 8.x (`petnest_db` - 8 normalized tables)
- **Environment:** XAMPP (Apache + MySQL)

---

## 🛠️ Local Setup Instructions

1. **Clone or Copy Project**:
    * Place the project inside your XAMPP `htdocs` directory:
      ```
      C:\xampp\htdocs\PetNest\
      ```

2. **Start Local Server**:
    * Open the **XAMPP Control Panel**.
    * Start **Apache** and **MySQL**.

3. **Import Database**:
    * Open your browser and navigate to `http://localhost/phpmyadmin/`.
    * Click on the **Import** tab.
    * Choose the file `database.sql` from the project root and click **Import / Go**.
    * The database `petnest_db` with all 8 tables and sample seed data will be created automatically.

4. **Run the Application**:
    * Visit: [http://localhost/PetNest/index.php](http://localhost/PetNest/index.php)

---

## 🔑 Pre-Configured Test Accounts

All pre-seeded test accounts use the password: **`password123`**

| Role | Email | Password | Features Accessible |
|---|---|---|---|
| **Admin** | `admin@petnest.com` | `password123` | User management, revenue & booking stats, review moderation |
| **Operator** | `operator@petnest.com` | `password123` | Keeper verification, emergency alert monitoring, dispute support |
| **Pet Keeper** | `sarah@petnest.com` | `password123` | Keeper dashboard, booking requests, profile editor |
| **Pet Keeper** | `david@petnest.com` | `password123` | Keeper profile & rate settings |
| **Pet Owner** | `janani@petnest.com` | `password123` | Pet CRUD, search keepers, booking creation, checkout |

---

## 👥 4-Way Team Division & Git Branching

Each team member works on their dedicated feature branch:

### 1. Member 1: Authentication, Pets & Search
- **Branch:** `feature/auth-pets-search`
- **Scope:** `login.php`, `register.php`, `logout.php`, `owner/pets.php`, `owner/add_pet.php`, `search.php`.

### 2. Member 2: Keeper Profile & Dashboard
- **Branch:** `feature/keeper-management`
- **Scope:** `keeper/profile.php`, `keeper_profile.php`, `keeper/dashboard.php`, `keeper/booking_requests.php`.

### 3. Member 3: Booking Engine & Payments
- **Branch:** `feature/booking-and-payments`
- **Scope:** `book.php`, `owner/my_bookings.php`, `payment/checkout.php`, `payment/receipt.php`.

### 4. Member 4: Admin, Operator, Ratings & Alerts
- **Branch:** `feature/admin-operator-ratings`
- **Scope:** `admin/dashboard.php`, `admin/users.php`, `admin/reviews.php`, `operator/dashboard.php`, `operator/verify_keepers.php`, `keeper/send_alert.php`, `owner/rate_keeper.php`.

---

### 🌿 Git Daily Commands

```bash
# Switch to develop and update
git checkout develop
git pull origin develop

# Create your feature branch (First time)
git checkout -b feature/your-branch-name

# Commit your changes
git add .
git commit -m "Brief description of changes"
git push -u origin feature/your-branch-name
```
