# EarlyBird Development Guidelines

## Architecture Overview

EarlyBird is a time-tracking application with a microservices architecture:

- **earlybird-back**: Symfony API backend (PHP 8.2 + PostgreSQL)
- **earlybird-dashboard**: React admin dashboard (TypeScript + Vite)
- **earlybird-front**: React user-facing application
- **reverse-proxy**: Nginx routing layer

Services communicate via REST APIs with JWT authentication. Data flows from frontend → reverse-proxy → backend API → PostgreSQL.

## Key Development Patterns

### API Design
- RESTful endpoints with OpenAPI/Swagger documentation
- JWT-based authentication with `lexik/jwt-authentication-bundle`
- Role-based access control (employee/manager/admin)
- Consistent error responses with JSON structure
- Database fields use `snake_case` (e.g., `phone_number`, `code_pin`)

### Frontend Architecture (Dashboard)
- **Routing**: TanStack Router with file-based routing
- **State**: Zustand for global state, TanStack Query for server state
- **Forms**: react-hook-form + Zod validation
- **UI**: shadcn/ui components with Radix UI primitives
- **Styling**: Tailwind CSS with custom design system
- **Naming**: camelCase for variables (e.g., `firstName`, `phoneNumber`, `codePin`)

### Backend Patterns (Symfony)
- **Entities**: Doctrine ORM with proper relationships
- **Controllers**: RESTful with OpenAPI annotations
- **Validation**: Symfony's validator component
- **Security**: Symfony Security with custom User entity
- **Migrations**: Doctrine migrations for schema changes

### Docker & Local Development
- **Setup**: Requires hosts file configuration for virtual hosts
- **Commands**:
  - `docker compose up --build` - Start full stack
  - `docker compose exec backend php bin/console doctrine:migrations:migrate` - Run migrations
  - `docker compose exec dashboard npm run dev` - Frontend dev server
- **Virtual Hosts**: `earlybird-front`, `earlybird-dashboard`, `earlybird-api`

## Common Workflows

### Adding New Features
1. **Backend**: Create entity, controller, update routes
2. **Database**: Add migration if schema changes needed
3. **Frontend**: Add API functions, components, routing
4. **Testing**: Add unit tests for critical logic

### User Management
- Users have roles: `employee`, `manager`, `admin`
- Optional `code_pin` field for PIN-based authentication
- Phone numbers stored as strings (flexible format)
- Passwords hashed with bcrypt

### Form Validation
- Use Zod schemas for TypeScript validation
- Backend validates required fields and data types
- Frontend shows real-time validation errors
- Password requirements: 8+ chars, 1 lowercase, 1 number

### API Integration
- Base URL configured via `VITE_API_BASE_URL` environment variable
- Error handling with try/catch and user-friendly messages
- Loading states managed with TanStack Query
- Optimistic updates for better UX

## Code Quality Standards

### TypeScript/React
- Strict TypeScript configuration
- Feature-based folder organization
- Custom hooks for reusable logic
- Consistent error boundaries

### PHP/Symfony
- PSR-12 coding standards
- Dependency injection pattern
- Repository pattern for data access
- Comprehensive OpenAPI documentation

### Testing
- PHPUnit for backend unit/integration tests
- Jest for frontend component tests
- API contract testing recommended

## Deployment & DevOps

### Local Development
- Docker Compose for consistent environments
- Hot reload enabled for frontend development
- Database volumes persist data between restarts

### Production Considerations
- Nginx reverse proxy handles routing
- Environment-specific configurations
- Database migrations run automatically
- Static asset optimization with Vite

## File Structure Conventions

```
earlybird-dashboard/src/
├── features/           # Feature-based modules
│   └── users/
│       ├── components/ # UI components
│       ├── data/       # API calls & types
│       └── index.tsx   # Feature entry point
├── components/ui/      # Reusable UI components
├── lib/               # Utilities & configurations
└── routes/            # TanStack Router definitions

earlybird-back/src/
├── Controller/        # API endpoints
├── Entity/           # Doctrine entities
├── Repository/       # Data access layer
└── Service/          # Business logic
```

## Common Gotchas

- **Hosts file**: Must add virtual host entries for local development
- **Database**: Uses PostgreSQL with specific Doctrine configuration
- **CORS**: Configured in Symfony for cross-origin requests
- **JWT**: Tokens expire and need refresh logic
- **Forms**: Password confirmation fields disabled until password touched
- **API responses**: Always check for error status before parsing JSON</content>
<parameter name="filePath">\\wsl.localhost\\Ubuntu-22.04\\home\\sharki\\T-DEV-700-project-PAR_3\\.github\\copilot-instructions.md