# VolunteerHub - JCI Surigao Wensie Volunteer Management System

VolunteerHub is a web-based platform tailored for **JCI Surigao Wensie** to manage community volunteers, organize events, dispatch skills-matched tasks, track engagement metrics, and facilitate organization-volunteer coordination.

---

## 🛠️ System Requirements

Before running the application on a new machine, ensure you have installed:

- **PHP**: `^8.2` or higher (with `pdo`, `pdo_sqlite` or `pdo_mysql`, `mbstring`, `openssl`, `curl` extensions enabled)
- **Composer**: `v2.x`
- **Node.js**: `v18.x` or `v20.x` (LTS recommended) & **npm**
- **Git**
- *(Optional)* **SQLite** or **MySQL / MariaDB** database server

---

## 🚀 Quick Setup & Installation Guide

Follow these step-by-step instructions to clone, set up, and run the project locally on Windows, macOS, or Linux.

### 1. Clone the Repository

Open your terminal or command prompt and run:

```bash
git clone https://github.com/leanDevs/VolunteerHub.git
cd VolunteerHub
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Install Node.js Dependencies

```bash
npm install
```

### 4. Environment Configuration

Copy the example environment file to create your local `.env` configuration file:

**On Windows (Command Prompt / PowerShell):**
```powershell
copy .env.example .env
```

**On macOS / Linux / Git Bash:**
```bash
cp .env.example .env
```

### 5. Setup Database

By default, the application is configured to use **SQLite** for zero-config local development.

1. **Create the SQLite database file:**
   - **Windows PowerShell:** `New-Item -ItemType File -Path database/database.sqlite -Force`
   - **Windows Command Prompt:** `type NUL > database\database.sqlite`
   - **Linux / macOS / Git Bash:** `touch database/database.sqlite`

2. *(Alternative - MySQL)*: If you prefer using MySQL, open `.env` and configure your database parameters:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=volunteerhub
   DB_USERNAME=root
   DB_PASSWORD=
   ```

### 6. Generate Application Key

```bash
php artisan key:generate
```

### 7. Create Storage Link

Create a symbolic link from `public/storage` to `storage/app/public` for file and avatar uploads:

```bash
php artisan storage:link
```

### 8. Run Migrations & Seed Database

Run database migrations to set up the tables and populate initial demo data (skills, events, users, and tasks):

```bash
php artisan migrate:fresh --seed
```

---

## 💻 Running the Application

To run the application locally, start both the PHP backend server and Vite frontend asset builder.

### Running in Two Terminal Windows

**Terminal 1 (Laravel Dev Server):**
```bash
php artisan serve
```
> Server will start at `http://127.0.0.1:8000`

**Terminal 2 (Vite Hot Reload):**
```bash
npm run dev
```

Now open your browser and navigate to `http://127.0.0.1:8000`.

### Railway uploaded-file storage

Railway's container filesystem is temporary. To keep profile photos, event photos, officer photos, and completion evidence between deployments:

1. In the Railway service, add a Volume mounted at `/app/storage/app/public`.
2. Deploy the application. The startup command recreates Laravel's `public/storage` link to that mounted directory.
3. If previously uploaded files are missing, restore them from a backup or upload them again; the volume cannot recover files already removed from a previous container.

The public filesystem disk uses Railway's `RAILWAY_VOLUME_MOUNT_PATH` when available. `PUBLIC_STORAGE_PATH` can override it for a different mount location.

---

## 🔑 Default Test User Credentials

After running `php artisan migrate:fresh --seed`, you can log in using any of the seeded accounts (Password for all test accounts is `password`):

| Role | Email | Password | Description |
| :--- | :--- | :--- | :--- |
| **System Admin** | `admin@volunteerhub.ph` | `password` | Full system administration and verification |
| **Organization** | `org@volunteerhub.ph` | `password` | JCI Surigao Wensie event & task management |
| **Volunteer** | `juan@volunteerhub.ph` | `password` | Volunteer user profile & task response |

---

## 🛠️ Common Useful Commands

- **Clear Cache:** `php artisan cache:clear && php artisan config:clear && php artisan route:clear`
- **Re-seed Database:** `php artisan migrate:fresh --seed`
- **Production Build:** `npm run build`

---

## 📄 License

This project is maintained for JCI Surigao Wensie.
