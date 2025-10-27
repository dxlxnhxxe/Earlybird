# Reverse Proxy Configuration

This directory contains the Nginx reverse proxy configuration for the EarlyBird application.

## Overview

The reverse proxy provides subdomain-based routing on port 80 using virtual hosts:

```
┌─────────────────────────────────────────────┐
│       Reverse Proxy (Port 80)               │
└─────────────────────────────────────────────┘
            │
            ├─► earlybird-api       → Backend (Port 8000)
            ├─► earlybird-dashboard → Dashboard (Port 5173)
            ├─► earlybird-front     → Frontend (Port 3000)
            └─► localhost           → Health check & redirect
```

## Routes

### API Virtual Host (`earlybird-api`)
- **URL**: http://earlybird-api
- **Target**: Backend service (Symfony)
- **Port**: 8000 (internal)
- **Features**:
  - Rate limiting (10 requests/second with burst of 20)
  - CORS headers enabled
  - Direct proxy to backend without path modifications
  - Example: `http://earlybird-api/users` → `http://backend:8000/users`

### Dashboard Virtual Host (`earlybird-dashboard`)
- **URL**: http://earlybird-dashboard
- **Target**: Dashboard service (React)
- **Port**: 5173 (internal)
- **Features**:
  - Static assets (JS, CSS, images) are cached for 1 hour
  - Full WebSocket support
  - Example: `http://earlybird-dashboard/` → `http://dashboard:5173/`

### Frontend Virtual Host (`earlybird-front`)
- **URL**: http://earlybird-front
- **Target**: Frontend service (React)
- **Port**: 3000 (internal)
- **Features**:
  - Static assets cached for 1 hour
  - Full proxy support with WebSocket upgrades
  - Example: `http://earlybird-front/` → `http://frontend:3000/`

### Health Check
- **Endpoint**: `/health`
- **Response**: `200 OK`
- **Purpose**: Used by Docker health checks and monitoring

## Security Features

1. **Rate Limiting**: API requests are limited to 10/second with burst capacity of 20
2. **Security Headers**:
   - `X-Frame-Options: SAMEORIGIN`
   - `X-Content-Type-Options: nosniff`
   - `X-XSS-Protection: 1; mode=block`
3. **CORS**: Configured for cross-origin requests
4. **Client Max Body Size**: 100MB for file uploads

## Configuration Files

- `Dockerfile`: Builds the Nginx container
- `nginx.conf`: Main Nginx configuration with routing rules

## Setup

### 1. Configure Local DNS

To use subdomain-based routing, you need to add entries to your hosts file:

**Windows**: `C:\Windows\System32\drivers\etc\hosts`
**Linux/Mac**: `/etc/hosts`

Add these lines:
```
127.0.0.1 earlybird-front
127.0.0.1 earlybird-dashboard
127.0.0.1 earlybird-api
```

On Windows, you may need to run your text editor as Administrator to edit the hosts file.

### 2. Start all services with reverse proxy:
```bash
docker compose up -d
```

### 3. Access the application:
- Frontend: http://earlybird-front
- Dashboard: http://earlybird-dashboard
- API: http://earlybird-api
- Health Check: http://localhost/health
- Root: http://localhost (redirects to frontend)

### View reverse proxy logs:
```bash
docker compose logs reverse-proxy
```

### Restart reverse proxy:
```bash
docker compose restart reverse-proxy
```
