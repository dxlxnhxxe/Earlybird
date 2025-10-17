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

From the repository root:

```bash
docker compose up --build
```

- Frontend: http://localhost:3000
- Backend API (Symfony): http://localhost:8080
- Database: PostgreSQL exposed on localhost:5432 (default creds symfony/symfony, db "database")

The frontend reads API base URL from the env variable `VITE_API_BASE_URL` (defaults to `http://localhost:8080`). To override, set it in `earlybird-front/.env` before building.

## 📝 Usage
1. **Create a Project:** Add a project or task in the dashboard.
2. **Start Tracking:** Click "Start" to begin the timer.
3. **Stop Tracking:** Click "Stop" to save the duration.
4. **View Reports:** Access the "Reports" tab to visualize and export your data.

---

## 🛠️ Built With
- **Frontend:** ReactJS
- **Backend:** PHP
- **Database:** (We don't know yet)