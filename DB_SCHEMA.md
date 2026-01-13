# Database Schema - EarlyBird

## Overview

This document describes the database schema for the EarlyBird application, a time tracking system built with **Symfony 6.x**, **Doctrine ORM**, and **PostgreSQL**.

---

## Entity Relationship Diagram

```mermaid
erDiagram
    USER {
        INT id PK "AUTO_INCREMENT"
        VARCHAR_255 firstname "NOT NULL"
        VARCHAR_255 lastname "NOT NULL"
        VARCHAR_255 email UK "NOT NULL, UNIQUE"
        VARCHAR_255 phone_number "NOT NULL"
        VARCHAR_255 password "NOT NULL"
        VARCHAR_255 role "NOT NULL, DEFAULT ROLE_EMPLOYEE"
        INT code_pin "NULLABLE"
    }

    TEAM {
        INT id PK "AUTO_INCREMENT"
        VARCHAR_255 name "NOT NULL"
        TEXT description "NULLABLE"
        INT manager_id FK,UK "NULLABLE, ON DELETE SET NULL"
    }

    TEAM_MEMBER {
        INT id PK "AUTO_INCREMENT"
        INT team_id FK "NOT NULL, ON DELETE CASCADE"
        INT user_id FK "NOT NULL, ON DELETE CASCADE"
        TIME start_time "NULLABLE"
        TIME end_time "NULLABLE"
    }

    CLOCK {
        INT id PK "AUTO_INCREMENT"
        INT team_member_id FK "NOT NULL, ON DELETE CASCADE"
        DATETIME timestamp "NOT NULL"
        VARCHAR_20 type "NOT NULL, arrival or departure"
    }

    REFRESH_TOKENS {
        SERIAL id PK "AUTO_INCREMENT"
        VARCHAR_128 refresh_token UK "NOT NULL, UNIQUE"
        VARCHAR_255 username "NOT NULL"
        TIMESTAMP valid "NOT NULL"
    }

    USER ||--o| TEAM : "manages (manager_id)"
    USER ||--o{ TEAM_MEMBER : "belongs to"
    TEAM ||--o{ TEAM_MEMBER : "contains"
    TEAM_MEMBER ||--o{ CLOCK : "records"
```

## Visual Relationship Diagram

```mermaid
flowchart TB
    subgraph Authentication
        REFRESH_TOKENS[(REFRESH_TOKENS)]
    end

    subgraph Core["Core Entities"]
        USER[(USER)]
        TEAM[(TEAM)]
        TEAM_MEMBER[(TEAM_MEMBER)]
        CLOCK[(CLOCK)]
    end

    USER -->|"1:N manages<br/>ON DELETE SET NULL"| TEAM
    USER -->|"1:N member of<br/>ON DELETE CASCADE"| TEAM_MEMBER
    TEAM -->|"1:N has<br/>ON DELETE CASCADE"| TEAM_MEMBER
    TEAM_MEMBER -->|"1:N records<br/>ON DELETE CASCADE"| CLOCK
```

## Data Flow Diagram

```mermaid
flowchart LR
    subgraph Users
        A[Admin]
        M[Manager]
        E[Employee]
    end

    subgraph System
        AUTH[Authentication<br/>JWT + Refresh Token]
        TEAMS[Team Management]
        TIME[Time Tracking]
    end

    A & M & E --> AUTH
    AUTH --> REFRESH_TOKENS[(REFRESH_TOKENS)]

    A --> TEAMS
    M --> TEAMS
    TEAMS --> TEAM[(TEAM)]
    TEAMS --> TEAM_MEMBER[(TEAM_MEMBER)]

    E --> TIME
    TIME --> CLOCK[(CLOCK)]

    TEAM_MEMBER --> CLOCK
```

---

## Tables

### User

Stores user account information and authentication data.

| Column       | Type         | Constraints              | Description                          |
|--------------|--------------|--------------------------|--------------------------------------|
| id           | INT          | PK, AUTO_INCREMENT       | Unique identifier                    |
| firstname    | VARCHAR(255) | NOT NULL                 | User's first name                    |
| lastname     | VARCHAR(255) | NOT NULL                 | User's last name                     |
| email        | VARCHAR(255) | NOT NULL, UNIQUE         | User's email (used for login)        |
| phone_number | VARCHAR(255) | NOT NULL                 | User's phone number                  |
| password     | VARCHAR(255) | NOT NULL                 | Hashed password                      |
| role         | VARCHAR(255) | NOT NULL, DEFAULT 'ROLE_EMPLOYEE' | User role (ROLE_ADMIN, ROLE_MANAGER, ROLE_EMPLOYEE) |
| code_pin     | INT          | NULLABLE                 | Optional PIN code                    |

---

### Team

Represents a team/group of users with a designated manager.

| Column      | Type         | Constraints                    | Description                |
|-------------|--------------|--------------------------------|----------------------------|
| id          | INT          | PK, AUTO_INCREMENT             | Unique identifier          |
| name        | VARCHAR(255) | NOT NULL                       | Team name                  |
| description | TEXT         | NULLABLE                       | Team description           |
| manager_id  | INT          | FK → User(id), UNIQUE, NULLABLE, ON DELETE SET NULL | Team manager |

---

### TeamMember

Join table linking users to teams with work schedule information.

| Column     | Type | Constraints                              | Description                    |
|------------|------|------------------------------------------|--------------------------------|
| id         | INT  | PK, AUTO_INCREMENT                       | Unique identifier              |
| team_id    | INT  | FK → Team(id), NOT NULL, ON DELETE CASCADE | Associated team              |
| user_id    | INT  | FK → User(id), NOT NULL, ON DELETE CASCADE | Associated user              |
| start_time | TIME | NULLABLE                                 | Scheduled work start time      |
| end_time   | TIME | NULLABLE                                 | Scheduled work end time        |

---

### Clock

Records clock-in and clock-out events for team members.

| Column         | Type         | Constraints                                    | Description                        |
|----------------|--------------|------------------------------------------------|------------------------------------|
| id             | INT          | PK, AUTO_INCREMENT                             | Unique identifier                  |
| team_member_id | INT          | FK → TeamMember(id), NOT NULL, ON DELETE CASCADE | Associated team membership       |
| timestamp      | DATETIME     | NOT NULL                                       | Time of the clock event            |
| type           | VARCHAR(20)  | NOT NULL                                       | Event type: `arrival` or `departure` |

---

### RefreshTokens

Stores JWT refresh tokens for authentication.

| Column        | Type         | Constraints        | Description                    |
|---------------|--------------|--------------------|--------------------------------|
| id            | SERIAL       | PK                 | Unique identifier              |
| refresh_token | VARCHAR(128) | NOT NULL, UNIQUE   | The refresh token string       |
| username      | VARCHAR(255) | NOT NULL           | Associated username            |
| valid         | TIMESTAMP    | NOT NULL           | Token expiration timestamp     |

**Indexes:**
- `idx_refresh_token` on `refresh_token`
- `idx_refresh_token_username` on `username`

---

## Relationships

| Parent Entity | Child Entity | Relationship | Description                                      |
|---------------|--------------|--------------|--------------------------------------------------|
| User          | Team         | One-to-Many  | A user (manager) can manage multiple teams       |
| Team          | TeamMember   | One-to-Many  | A team has multiple members                      |
| User          | TeamMember   | One-to-Many  | A user can be a member of multiple teams         |
| TeamMember    | Clock        | One-to-Many  | A team member has multiple clock entries         |

---

## Cascade Behavior

| Relation                | On Delete    |
|-------------------------|--------------|
| Team → Manager (User)   | SET NULL     |
| TeamMember → Team       | CASCADE      |
| TeamMember → User       | CASCADE      |
| Clock → TeamMember      | CASCADE      |

---

## Source Files

- `earlybird-back/src/Entity/User.php`
- `earlybird-back/src/Entity/Team.php`
- `earlybird-back/src/Entity/TeamMember.php`
- `earlybird-back/src/Entity/Clock.php`
- `earlybird-back/migrations/`
