# **EarlyBird – Technical Documentation**
*Time Tracking Application with Clock-In/Clock-Out and KPIs*

---

## **1. Context and Objectives**
**Time Manager** is an application designed to allow **employees** to record their arrival/departure times and **managers** to manage teams and view KPIs.
**Key Objectives**:
- Real-time tracking of working hours.
- Generation of **reports and KPIs** (e.g., lateness rate, average hours).
- **User and team management** (CRUD operations).
- **Security**: JWT authentication and role-based access control.

**DevOps Philosophy**:
- Automate testing, builds, and deployments.
- Use **Docker** and **GitHub Actions** for continuous integration.

---

## **2. Technological Choices**

| **Layer**          | **Technology**               | **Justification**                                                                 |
|--------------------|------------------------------|-----------------------------------------------------------------------------------|
| **Frontend**       | React.js                     | Modern framework for a reactive and scalable UI.                                |
| **Backend**        | PHP (Symfony)                | Robust framework for RESTful APIs, security, and database management.            |
| **Database**       | PostgreSQL                   | Reliable relational database, well-supported by Symfony.                         |
| **Authentication** | JWT                          | Secures API requests and manages user sessions.                                  |
| **Backend Tests**  | PHPUnit                      | Unit and functional tests for API routes.                                        |
| **Frontend Tests** | Jest                         | Tests React components.                                                          |
| **CI/CD**          | GitHub Actions               | Automates builds, tests, and deployments.                                        |
| **Containerization** | Docker + Docker Compose    | Reproducible environments for development and production.                        |
| **Reverse Proxy**  | Nginx                        | Routes requests to frontend/backend and exposes public ports.                     |
| **Storage**        | Docker Volumes               | Persists database and file data.                                                 |
| **Notifications**  | Mailpit (fake SMTP)          | Simulates email notifications for reminders.                                     |

---

## **3. Overall Architecture**
The application follows a **modular monolithic architecture** (separate frontend/backend, containerized).
Architecture diagram:

![architecture](img/architecture.png)

---
### **3.1. Component Description**

#### **Frontend (React.js)**
- **Pages**:
  - Login/Registration (`/login`).
  - Dashboard (working hours, KPIs).
  - User/Team Management (manager-only).
  - Time Entry History (`/clocks`).
- **Libraries**:
  - `axios` for API requests.
  - `react-router-dom` for navigation.
  - `Chart.js` for KPI visualizations.

#### **Backend (Symfony)**
- **Modules**:
  - **Auth**: Registration/login, JWT token generation.
  - **Users**: CRUD for users (employees/managers).
  - **Teams**: CRUD for teams.
  - **Clocks**: Records arrival/departure times.
  - **Reports**: Generates reports and KPIs.
- **API Routes (Examples)**:
  | Method | Endpoint               | Description                                      |
  |--------|------------------------|--------------------------------------------------|
  | POST   | `/api/login`           | Authenticates user (returns JWT token).          |
  | GET    | `/api/users`           | Lists users (manager-only).                      |
  | POST   | `/api/clocks`          | Records arrival/departure.                       |
  | GET    | `/api/reports`         | Generates global reports (KPIs).                 |

#### **Database (PostgreSQL)**
- **Main Tables**:
  ```sql
  CREATE TABLE users (
      id SERIAL PRIMARY KEY,
      email VARCHAR(255) UNIQUE,
      password VARCHAR(255),
      role ENUM('employee', 'manager'),
      first_name VARCHAR(255),
      last_name VARCHAR(255)
  );

  CREATE TABLE teams (
      id SERIAL PRIMARY KEY,
      name VARCHAR(255),
      description TEXT,
      manager_id INT REFERENCES users(id)
  );

  CREATE TABLE clocks (
      id SERIAL PRIMARY KEY,
      user_id INT REFERENCES users(id),
      clock_in TIMESTAMP,
      clock_out TIMESTAMP NULL,
      date DATE
  );
  ```

#### **Docker**
- **Files**:
  - `docker-compose.yml`: Services for development (backend, frontend, DB, Nginx).
  - `docker-compose.prod.yml`: Production configuration.
- **Example Service**:
  ```yaml
  services:
    backend:
      build: ./backend
      ports:
        - "8000:8000"
      depends_on:
        - db
      environment:
        - DATABASE_URL=postgresql://user:pass@db:5432/timemanager
  ```

#### **GitHub Actions**
- **CI Workflow**:
  ```yaml
  jobs:
    test:
      runs-on: ubuntu-latest
      steps:
        - uses: actions/checkout@v4
        - run: docker-compose up -d
        - run: docker-compose exec backend php bin/phpunit
        - run: docker-compose exec frontend npm test
  ```

---
## **4. Key Workflows**

### **4.1. Authentication**
1. User logs in via `/login` (email + password).
2. Backend generates a **JWT token** and returns it.
3. Frontend stores the token and includes it in request headers (`Authorization: Bearer <token>`).

### **4.2. Time Tracking**
1. Employee clicks "Clock-In" → Frontend sends `POST /api/clocks` with `clock_in`.
2. Backend records the time in the `clocks` table.
3. For "Clock-Out," frontend sends `PATCH /api/clocks/{id}` with `clock_out`.

### **4.3. Report Generation**
1. Manager selects a period and team.
2. Frontend calls `GET /api/reports?team_id=1&start_date=2025-01-01&end_date=2025-01-31`.
3. Backend calculates KPIs (e.g., average hours/day) and returns the data.

---
## **5. Security**
- **Roles**:
  - **Employee**: Access to personal dashboard and time entries.
  - **Manager**: Access to team reports and user management.
- **Symfony Middleware**:
  ```php
  // Example access control
  #[IsGranted('ROLE_MANAGER')]
  public function getTeamReport(int $teamId): JsonResponse { ... }
  ```
- **Best Practices**:
  - Password hashing (bcrypt).
  - Input validation (e.g., `clock_in` < `clock_out`).
  - CSRF protection for forms.

---
## **6. Implemented KPIs**
1. **Lateness Rate**:
   - `% of arrivals after 9:00 AM`.
2. **Average Daily Hours**:
   - `SUM(clock_out - clock_in) / COUNT(*)` per user/team.

---
## **7. DevOps and Deployment**
- **Docker Compose**:
  - **Dev**: Services with hot-reload (e.g., `volumes` for code).
  - **Prod**: Optimized build, ports exposed via Nginx.
- **GitHub Actions**:
  - Runs tests on every push.
  - Generates coverage reports (e.g., `phpunit --coverage-text`).
- **Reverse Proxy (Nginx)**:
  - Routing:
    - `/api/*` → Symfony Backend.
    - `/*` → React Frontend.

---
## **8. Testing**
- **Backend**:
  ```php
  public function testClockIn(): void
  {
      $client = static::createClient();
      $client->request('POST', '/api/clocks', ['json' => ['user_id' => 1]]);
      $this->assertResponseStatusCodeSame(201);
  }
  ```
- **Frontend**:
  ```javascript
  test('renders login form', () => {
      render(<Login />);
      expect(screen.getByLabelText('Email')).toBeInTheDocument();
  });
  ```

---
## **9. Accessibility and UX**
- **Criteria**:
  - Color contrast (WCAG compliance).
  - Keyboard navigation.
  - Clear error messages.
- **Tools**:
  - Lighthouse (automated audits).
  - Axe (accessibility tests).

---
## **10. User Documentation**
### **README.md**
```markdown
# Time Manager

## Setup
1. Clone the repository:
   ```bash
   git clone https://github.com/EPITEHCMSC/time-manager.git
   ```
2. Start containers:
   ```bash
   docker-compose up -d
   ```
3. Access:
   - Frontend: `http://localhost:3000`
   - Backend: `http://localhost:8000/api`
   - Mailpit: `http://localhost:8025` (for emails).

## Roles
- **Employee**: `/dashboard`, `/clocks`.
- **Manager**: `/teams`, `/reports`.
```

---
## **11. Final Presentation**
For the defense, prepare:
1. **Demo**:
   - Time tracking workflow and report generation.
   - CI logs (GitHub Actions).
2. **Justify Choices**:
   - Symfony for its maturity and bundles (e.g., `lexik/jwt-authentication`).
   - React for its reactivity and ecosystem.
3. **Future Improvements**:
   - Push notifications for lateness.
   - Mobile app (React Native).

---
**Appendices**:
- [Figma Mockups](#)
- [GitHub Repository](#)
- [Test Coverage Report](#)

---
**Need clarification on any point?** 😊
For example:
- JWT configuration details?
- Example KPI query?
- Full database schema?