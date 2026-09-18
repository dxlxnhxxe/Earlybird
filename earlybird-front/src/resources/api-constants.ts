// Base URL for the Earlybird API. Configurable at build time via
// REACT_APP_API_BASE_URL (see .env / Render service env vars); falls
// back to the docker-compose hostname for local dev.
export const API_BASE_URL: string =
    (process.env.REACT_APP_API_BASE_URL as string) || 'http://earlybird-api'
