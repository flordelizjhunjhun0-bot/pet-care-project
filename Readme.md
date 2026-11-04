# 🐾 Pet-Care: Veterinary Clinic Record System with Vaccination Scheduling

## 📌 Project Overview

**Pet-Care** is a web-based Veterinary Clinic Record System with Vaccination Scheduling developed to help manage pet information and veterinary clinic records efficiently.

The system provides a centralized platform where pet owners can manage their pets' information, vaccination records, medical history, and appointments. Administrators can manage user accounts, monitor pet records, oversee vaccination schedules, manage appointments, and generate reports.

This project was developed as a requirement for **Technical Elective III (Software Development)**.

## 🎯 Objectives

- To develop a web-based veterinary clinic record management system.
- To provide pet owners with a platform for managing their pets' records.
- To organize vaccination schedules and medical histories.
- To allow administrators to manage users, pets, vaccines, and appointments.
- To improve the accessibility and organization of veterinary clinic information.

## ✨ Features and Functionalities

### 👤 Pet Owner Module
- User registration and login.
- Account approval and authentication.
- Add, view, edit, and delete pet records.
- Manage vaccination records.
- View vaccination schedules.
- Add and manage medical history.
- Book and manage appointments.
- View personal pet information and records.

### 🛠️ Administrator Module
- Administrator login.
- Dashboard with clinic statistics.
- Manage and approve user accounts.
- View and manage registered pets.
- Manage vaccination records.
- Manage medical records.
- View and update appointment statuses.
- Generate and print system reports.

## 💻 Technologies Used

| Technology | Purpose |
|---|---|
| PHP | Backend development and server-side processing |
| MySQL | Database management |
| HTML5 | Web page structure |
| CSS3 | User interface and styling |
| JavaScript | Client-side interactions |
| Bootstrap / Custom CSS | Interface styling, if included in the project |
| XAMPP | Local development environment |
| InfinityFree | Web hosting and deployment |
| Git | Version control |
| GitHub | Source code repository |

## 🗄️ Database Structure

The system uses a MySQL database named `project`.

Main database tables:

| Table | Description |
|---|---|
| `users` | Stores user and administrator account information |
| `pets` | Stores registered pet information |
| `vaccines` | Stores vaccination records and schedules |
| `medical_history` | Stores pet medical records |
| `appointments` | Stores appointment information and status |

## ⚙️ Installation and Setup

### Requirements

- XAMPP
- PHP
- MySQL
- Web browser
- Git (optional, for cloning the repository)

### 1. Clone the Repository

Open your terminal and execute:

```bash
git clone YOUR_GITHUB_REPOSITORY_URL
```

Alternatively, download the repository as a ZIP file from GitHub.

### 2. Move the Project Folder

Extract or copy the project folder into:

```text
C:\xampp\htdocs\
```

### 3. Start XAMPP

Open XAMPP Control Panel and start:

- Apache
- MySQL

### 4. Create the Database

1. Open `http://localhost/phpmyadmin`.
2. Create a database named `project`.
3. Import the provided `project.sql` file.
4. Verify that all required tables are present.

### 5. Configure Database Connection

Open:

```text
db/db.php
```

Configure the database connection according to your local XAMPP setup.

Example:

```php
$conn = new mysqli(
    "localhost",
    "root",
    "",
    "project"
);
```

Use the actual connection settings required by your environment.

### 6. Run the Application

Open your browser and visit:

```text
http://localhost/Pet_Care/Account/login.php
```

Register a new pet owner account or log in using an existing authorized administrator account.

## 🌐 Deployment

The Pet-Care application is deployed using **InfinityFree**.

**Live Website:** https://petcare.ifree.page

The deployed application uses a hosted PHP environment and MySQL database. Database connection settings must be configured using the hosting provider's database credentials.

## 🔀 Development Workflow

Git and GitHub were used to manage source code changes and development progress.

The project uses the following Git workflow:

- `main` – Main project branch.
- `improvement` – Branch used for project improvements and updates.
- Commits – Record changes made during development.
- Merge – Integrate improvements into the main branch.
- Push – Upload committed changes to the GitHub repository.

## 🔐 Security Features

- Password hashing for account credentials.
- Prepared SQL statements to help prevent SQL injection.
- Session-based authentication.
- Role-based access control for administrators and pet owners.
- Input validation and output escaping.
- CSRF token validation for protected form submissions.
- Pet ownership verification for user records.

## 📁 Project Structure

```text
Pet_Care/
│
├── Account/
│   ├── login.php
│   ├── register.php
│   └── logout.php
│
├── Admin/
│   ├── admin_dashboard.php
│   ├── admin_appointment.php
│   ├── admin_medical.php
│   ├── admin_vaccine.php
│   ├── manage_pets.php
│   ├── manage_users.php
│   ├── reports.php
│   └── admin.css
│
├── User/
│   ├── user_dashboard.php
│   ├── add_pet.php
│   ├── my_pet.php
│   ├── view.php
│   ├── add_vaccine.php
│   ├── add_medical.php
│   └── add_appointment.php
│
├── db/
│   └── db.php
│
├── project.sql
└── README.md
```

*Note: The structure above shows the main project files. Other edit, delete, and supporting files may also be included.*

## 👥 System Users

**Administrator**
- Manages registered accounts.
- Oversees pet and veterinary records.
- Manages appointments and generates reports.

**Pet Owner**
- Manages personal pet information.
- Maintains vaccination and medical records.
- Submits and manages appointments.

## 📌 Project Information

**Project Title:** Pet-Care: Veterinary Clinic Record System with Vaccination Scheduling

**Course:** Technical Elective III (Software Development)

**Program:** Bachelor of Science in Computer Engineering

**Development Tools:** PHP, MySQL, HTML, CSS, JavaScript, XAMPP, Git, and GitHub

## 📄 License

This project was developed for academic purposes as part of a Technical Elective III software development requirement.