/** Cliente HTTP base. En las siguientes fases se agregan auth y más módulos. */

const API_BASE = import.meta.env.VITE_API_BASE_URL ?? '/api/v1'

export interface DatabaseHealth {
  status: 'ok' | 'error'
  name: string | null
  replica_set: string | null
  transactions: boolean
  message?: string
}

export interface HealthResponse {
  app: string
  version: string
  status: 'ok' | 'degraded'
  environment: string
  timestamp: string
  database: DatabaseHealth
}

/**
 * Consulta el estado de la API y de MongoDB.
 * Un estado `degraded` también es una respuesta válida: la interfaz lo
 * muestra como advertencia en lugar de romper la página.
 */
export async function getHealth(signal?: AbortSignal): Promise<HealthResponse> {
  const response = await fetch(`${API_BASE}/health`, {
    headers: { Accept: 'application/json' },
    signal,
  })

  const body = (await response.json().catch(() => null)) as HealthResponse | null

  if (!body || typeof body.status !== 'string') {
    throw new Error(`Respuesta inválida de la API (HTTP ${response.status})`)
  }

  return body
}
