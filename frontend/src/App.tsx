import { useEffect, useState } from 'react'
import './App.css'
import { getHealth, type HealthResponse } from './lib/api'

type HealthState =
  | { kind: 'loading' }
  | { kind: 'ready'; data: HealthResponse }
  | { kind: 'error'; message: string }

const FEATURES = [
  {
    title: 'Divisas sin sorpresas',
    body: 'Cada movimiento conserva su tipo de cambio; el Mapa del Dinero convierte a MXN, USD o EUR.',
  },
  {
    title: 'Auditoría completa',
    body: 'Anticipos, facturas y gastos con bitácora inmutable, lista para exportar a PDF y Excel.',
  },
  {
    title: 'Portal del cliente',
    body: 'Tus clientes consultan avance y montos en modo lectura, sin ver tarifas internas.',
  },
]

function StatusRow({ label, value, ok }: { label: string; value: string; ok?: boolean }) {
  return (
    <div className="status-row">
      <span className="status-label">{label}</span>
      <span className={`status-value ${ok === false ? 'is-bad' : ok ? 'is-good' : ''}`}>
        {value}
      </span>
    </div>
  )
}

function App() {
  const [state, setState] = useState<HealthState>({ kind: 'loading' })

  useEffect(() => {
    const controller = new AbortController()

    getHealth(controller.signal)
      .then((data) => setState({ kind: 'ready', data }))
      .catch((error: unknown) => {
        if (controller.signal.aborted) return
        setState({
          kind: 'error',
          message: error instanceof Error ? error.message : 'Error inesperado',
        })
      })

    return () => controller.abort()
  }, [])

  const health = state.kind === 'ready' ? state.data : null
  const database = health?.database ?? null
  const badge =
    state.kind === 'loading'
      ? { text: 'Verificando', tone: 'pending' }
      : state.kind === 'error' || health?.status !== 'ok'
        ? { text: 'Degradado', tone: 'bad' }
        : { text: 'Operativo', tone: 'good' }

  return (
    <div className="page">
      <div className="glow glow-left" aria-hidden="true" />
      <div className="glow glow-right" aria-hidden="true" />

      <header className="topbar">
        <div className="brand">
          <span className="brand-mark" aria-hidden="true">G</span>
          <span className="brand-name">GooMaps</span>
        </div>
        <span className="phase-chip">Fase 1 · Infraestructura</span>
      </header>

      <main className="hero">
        <section className="hero-copy">
          <p className="eyebrow">Gestión de costos · Proyectos de software</p>
          <h1>
            El <span className="gold">Mapa del Dinero</span> de cada proyecto,
            de la cotización al último peso.
          </h1>
          <p className="lede">
            GooMaps reúne presupuestos, horas, anticipos, facturas y divisas en un solo
            lugar. Una gráfica de flujo muestra a dónde se va el dinero y una bitácora
            inmutable respalda cada movimiento.
          </p>

          <ul className="highlights">
            <li><span className="tick">01</span> Costos extra necesarios y opcionales con recálculo en vivo.</li>
            <li><span className="tick">02</span> Tipos de cambio congelados al registrar cada movimiento.</li>
            <li><span className="tick">03</span> Reportes de auditoría con divisa y tipo de cambio.</li>
          </ul>
        </section>

        <aside className="status-card" aria-live="polite">
          <div className="status-header">
            <div>
              <p className="card-eyebrow">Diagnóstico</p>
              <h2>Estado del sistema</h2>
            </div>
            <span className={`badge badge-${badge.tone}`}>{badge.text}</span>
          </div>

          <div className="status-body">
            <StatusRow
              label="API Laravel"
              value={health ? `v${health.version} · ${health.environment}` : '—'}
              ok={health?.status === 'ok'}
            />
            <StatusRow
              label="MongoDB"
              value={database?.name ?? '—'}
              ok={database?.status === 'ok'}
            />
            <StatusRow
              label="Replica set"
              value={database?.replica_set ?? '—'}
              ok={Boolean(database?.replica_set)}
            />
            <StatusRow
              label="Transacciones"
              value={database?.transactions ? 'Habilitadas' : 'No disponibles'}
              ok={database?.transactions}
            />
          </div>

          {state.kind === 'error' && (
            <p className="status-error">
              No fue posible contactar la API. Revisa <code>docker compose ps</code> y los
              logs del backend.
              <span className="error-detail">{state.message}</span>
            </p>
          )}

          {database?.message && <p className="status-error">{database.message}</p>}

          <div className="status-footer">
            <span>MongoDB 8 · replica set rs0</span>
            <span>{health ? new Date(health.timestamp).toLocaleString('es-MX') : 'Conectando…'}</span>
          </div>
        </aside>
      </main>

      <section className="feature-grid" aria-label="Alcance del sistema">
        {FEATURES.map((feature) => (
          <article className="feature" key={feature.title}>
            <span className="feature-rule" aria-hidden="true" />
            <h3>{feature.title}</h3>
            <p>{feature.body}</p>
          </article>
        ))}
      </section>

      <footer className="footer">
        <span>GooMaps · Instituto Tecnológico de Milpa Alta II</span>
        <span className="stack">Laravel 13 · MongoDB 8 · React 19 · Docker</span>
      </footer>
    </div>
  )
}

export default App
