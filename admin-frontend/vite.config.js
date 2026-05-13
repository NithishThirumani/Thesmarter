import { defineConfig, loadEnv } from 'vite'
import react from '@vitejs/plugin-react'

// Dev UI on :5173; browser requests /api/* → proxied to Laravel ORIGIN (same path — proxy forwards /api/...).
// Target examples: http://127.0.0.1 (nginx :80), http://127.0.0.1:8000 (php artisan serve).
// Docker Compose frontend container: set VITE_API_PROXY_TARGET=http://webserver on the process env (see docker-compose.yml).
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  const apiProxyTarget =
    env.VITE_API_PROXY_TARGET || 'http://127.0.0.1'

  return {
    plugins: [react()],
    server: {
      host: '0.0.0.0',
      port: 5173,
      allowedHosts: ['frontend', 'frontend.local.bizwy.in', 'merchant.thesmartr.com'],
      proxy: {
        '/api': {
          target: apiProxyTarget,
          changeOrigin: true,
          // Only verify TLS when proxy target is https (local http:// avoids bogus SSL errors).
          secure: String(apiProxyTarget).startsWith('https'),
        },
      },
    },
  }
})
