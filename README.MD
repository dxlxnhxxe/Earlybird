# EarlyBird ⏰

**A simple and efficient time tracking module to monitor time spent on projects and tasks.**

---

## 🌟 Features
- **Manual/Auto Tracking:** Start and stop the timer for each task.
- **Project-Based Tracking:** Associate time with specific projects or clients.
- **Detailed Reports:** Export your data as CSV or PDF for analysis.
- **User-Friendly Dashboard:** Visualize your time at a glance.

---

## 🚀 Getting Started

### Prerequisites
- Docker Desktop (recommended)
- Or, manually:
   - Frontend: Node.js (for React/Vite)
   - Backend: PHP 8.2+ (with Composer)
   - Database: PostgreSQL 16

---

### Installation

#### Frontend (ReactJS)
1. Navigate to the `earlybird-front` directory:
   ```bash
   cd earlybird-front
   ```
2. Install dependencies:
   ```bash
   npm install
   ```
3. Start the development server:
   ```bash
   npm start
   ```

#### Backend (PHP)
1. Navigate to the `app` directory:
   ```bash
   cd app
   ```
2. Install PHP dependencies:
   ```bash
   composer install
   ```
3. Configure your database in `.env` (see `.env.example`).
4. Start the PHP server:
   ```bash
   php -S localhost:8000
   ```

---

### Run everything with Docker

**Step 1: Configure local DNS**

**Option A - Automatic (Recommended):**

Run the setup script for your OS:
- **Windows**: Right-click PowerShell → "Run as Administrator" → `.\setup-hosts.ps1`
- **Linux/Mac**: `sudo ./setup-hosts.sh`

**Option B - Manual:**

Add these lines to your hosts file:
- **Windows**: `C:\Windows\System32\drivers\etc\hosts` (run as Administrator)
- **Linux/Mac**: `/etc/hosts`

```
127.0.0.1 earlybird-front
127.0.0.1 earlybird-dashboard
127.0.0.1 earlybird-api
```

**Step 2: Start the services**

From the repository root:

```bash
docker compose up --build
```

**Step 3: Access the application**

- **Main Application:** http://earlybird-front (Frontend)
- **Dashboard:** http://earlybird-dashboard (Manager Dashboard)
- **API:** http://earlybird-api (Backend API)
- **Health Check:** http://localhost/health

The reverse proxy uses subdomain-based virtual hosts on port 80:
- `earlybird-front` → Frontend (React)
- `earlybird-dashboard` → Manager Dashboard (React)
- `earlybird-api` → Backend API (Symfony)

**Architecture:**
```
Port 80 (Reverse Proxy - Virtual Hosts)
    ├── earlybird-api       → Backend (Symfony, internal port 8000)
    ├── earlybird-dashboard → Dashboard (React, internal port 5173)
    ├── earlybird-front     → Frontend (React, internal port 3000)
    └── localhost           → Health check & redirect
```

**Database:** PostgreSQL (internal access only, not exposed externally)
- Default credentials: symfony/symfony
- Database name: "database"

For more details about the reverse proxy configuration, see [reverse-proxy/README.md](reverse-proxy/README.md).

## 📝 Usage
1. **Create a Project:** Add a project or task in the dashboard.
2. **Start Tracking:** Click "Start" to begin the timer.
3. **Stop Tracking:** Click "Stop" to save the duration.
4. **View Reports:** Access the "Reports" tab to visualize and export your data.

---

## 🛠️ Built With
- **Frontend:** ReactJS
- **Backend:** PHP
- **Database:** PostgreSQL
