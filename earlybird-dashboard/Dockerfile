# Build stage
FROM node:20-alpine AS builder

WORKDIR /app

# Render passes dashboard env vars (from render.yaml) as Docker build ARGs
# automatically, but only for ARGs this Dockerfile actually declares. Vite
# bakes import.meta.env.VITE_* values in at build time (inside `pnpm run
# build` below), so without this the build silently falls back to each
# file's hardcoded default (http://earlybird-api) instead of the real
# backend URL.
ARG VITE_API_BASE_URL

# Copy package files
COPY package.json pnpm-lock.yaml pnpm-workspace.yaml ./

# Install pnpm and dependencies
RUN npm install -g pnpm && pnpm install --frozen-lockfile

# Copy source files
COPY . .

# Build the application
RUN pnpm run build

# Production stage
FROM nginx:alpine

# Copy built files to nginx
COPY --from=builder /app/dist /usr/share/nginx/html

# Copy nginx configuration
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Expose port
EXPOSE 5173

# Start nginx
CMD ["nginx", "-g", "daemon off;"]
