# GooMaps

**Sistema de gestión de costos de proyectos de software con Mapa del Dinero.**

GooMaps permite a un gestor controlar el presupuesto de cada proyecto (horas,
tarifas por rol, costos extra, anticipos, facturas, gastos y divisas), dejar
rastro auditable de cada movimiento financiero y mostrar al cliente en qué se
está gastando su dinero mediante una gráfica de flujo llamada **Mapa del
Dinero**.

La especificación funcional completa (licitación, reglas de negocio RN-01 a
RN-23, procesos, casos de uso CU-01 a CU-16, cronograma e investigación) vive en
[`docs/Gooma.md`](docs/Gooma.md) y es la fuente de verdad del proyecto.

> **Estado actual: Fase 1 completada.** La infraestructura Docker, la API base y
> la SPA ya levantan con un solo comando. Los módulos de negocio se agregan en
> las siguientes fases (ver [Roadmap](#roadmap)).

---

## Stack

| Capa | Tecnología |
|---|---|
| Backend | Laravel 13 · API REST en `/api/v1` |
| Base de datos | MongoDB 8 como **replica set de un nodo** (transacciones) |
| Driver | `mongodb/laravel-mongodb` + extensión PHP `mongodb` |
| Frontend | React 19 + Vite 8 + TypeScript |
| Autenticación | Laravel Sanctum con tokens (fase 2) |
| Reportes | DomPDF + Maatwebsite/Excel (fase 5) |
| Gráfica | d3-sankey (fase 6) |
| Orquestación | Docker Compose |

## Arquitectura

```
┌────────────────────┐        /api/v1 (proxy Vite)        ┌─────────────────────┐
│  Navegador         │ ─────────────────────────────────► │  frontend (React)   │
│  localhost:5173    │                                    │  Vite dev server    │
└────────────────────┘                                    └──────────┬──────────┘
                                                                     │ HTTP
                                                          ┌──────────▼──────────┐
                                                          │  backend (Laravel)  │
                                                          │  localhost:8000     │
                                                          └──────────┬──────────┘
                                                                     │ driver mongodb
                                                          ┌──────────▼──────────┐
                                                          │  mongo (rs0)        │
                                                          │  localhost:27017    │
                                                          └─────────────────────┘
```

- El navegador solo habla con Vite; Vite reenvía `/api` al backend, así no hay
  problemas de CORS en desarrollo.
- `mongo-init` inicializa el replica set `rs0` de forma idempotente y termina.
- El backend espera a que el replica set esté listo antes de arrancar.

## Requisitos

Obligatorio:

- Docker Engine 24+ y Docker Compose v2+ (`docker compose version`).
- Puertos libres: `8000` (API), `5173` (frontend) y `27019` (MongoDB). Se pueden
  cambiar en el `.env` de la raíz. El puerto 27019 se usa por defecto para no
  chocar con instalaciones locales de MongoDB; dentro de Docker el servicio
  siempre escucha en 27017.

Opcional (no es necesario para correr el proyecto): PHP 8.3+, Composer, Node 22+
y npm para ejecutar herramientas fuera de Docker.

## Instalación con Docker

```bash
# 1. Clona el repositorio
git clone <url-del-repositorio> goomaps
cd goomaps

# 2. (Opcional) personaliza puertos y variables
cp .env.example .env

# 3. Construye y levanta todo
docker compose up --build
```

La primera ejecución tarda unos minutos: compila la extensión de MongoDB para
PHP e instala dependencias de Composer y npm. Las siguientes son mucho más
rápidas.

Cuando termine:

| Servicio | URL |
|---|---|
| Frontend | http://localhost:5173 |
| API (salud) | http://localhost:8000/api/v1/health |
| Raíz de la API | http://localhost:8000 |
| MongoDB | `mongodb://localhost:27019/goomaps` |

### Verificar que todo quedó bien

```bash
# 1. Salud de la API + MongoDB (debe decir "status": "ok")
curl -s http://localhost:8000/api/v1/health

# 2. La SPA y su proxy hacia la API
curl -s http://localhost:5173/api/v1/health

# 3. El replica set está activo
docker compose exec mongo mongosh --quiet --eval 'rs.status().set'
# → rs0

# 4. Logs en vivo
docker compose logs -f backend
```

En el navegador, http://localhost:5173 muestra la portada de GooMaps con una
tarjeta de diagnóstico: API, MongoDB, replica set y transacciones habilitadas.

### Comandos útiles

```bash
docker compose up -d              # levantar en segundo plano
docker compose down               # detener (conserva datos)
docker compose down -v            # detener y borrar datos de Mongo y volúmenes
docker compose build --no-cache   # reconstruir imágenes desde cero
docker compose exec backend php artisan ...   # cualquier comando de Laravel
docker compose exec mongo mongosh goomaps     # consola de MongoDB
docker compose exec frontend npm run build    # verificar TypeScript
```

## Variables de entorno

### Raíz (`.env`, opcional)

Todas tienen default en `docker-compose.yml`; el archivo solo sirve para
sobrescribirlas.

| Variable | Default | Descripción |
|---|---|---|
| `BACKEND_PORT` | `8000` | Puerto público de la API |
| `FRONTEND_PORT` | `5173` | Puerto público del frontend |
| `MONGO_PORT` | `27019` | Puerto público de MongoDB |
| `MONGO_DATABASE` | `goomaps` | Base de datos usada por Laravel |
| `FX_API_URL` | `https://api.frankfurter.app` | Proveedor de tipos de cambio (fase 4) |
| `FX_CACHE_TTL` | `43200` | Segundos de caché del tipo de cambio |
| `FX_FALLBACK_USD` | `17.50` | MXN por USD si la API falla |
| `FX_FALLBACK_EUR` | `19.00` | MXN por EUR si la API falla |

### Backend (`backend/.env`)

Se crea automáticamente a partir de `backend/.env.example` dentro del
contenedor; la `APP_KEY` se genera sola si falta. Las variables más relevantes:

| Variable | Valor en Docker | Notas |
|---|---|---|
| `APP_NAME` | `GooMaps` | Nombre visible |
| `APP_URL` | `http://localhost:8000` | URL de la API |
| `FRONTEND_URL` | `http://localhost:5173` | Usada por CORS y enlaces |
| `APP_TIMEZONE` | `America/Mexico_City` | Fechas de bitácora y reportes |
| `DB_CONNECTION` | `mongodb` | Única base soportada |
| `DB_HOST` / `DB_PORT` | `mongo` / `27017` | Nombre del servicio en Compose |
| `DB_DATABASE` | `goomaps` | Base de datos |
| `DB_DSN` | vacío | Alternativa para Atlas u otro clúster |
| `SESSION_DRIVER` | `file` | La API es stateless (tokens Sanctum) |
| `CACHE_STORE` | `file` | Caché local |
| `QUEUE_CONNECTION` | `sync` | Sin worker en desarrollo |

El `.env` real nunca se versiona.

## Usuarios de prueba

Se crean con los seeders en la **fase 2**:

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | `admin@goomaps.local` | `password` |
| Gestor | `gestor@goomaps.local` | `password` |
| Cliente | `cliente@goomaps.local` | `password` |

## Pruebas

```bash
docker compose exec backend php artisan test
```

En la fase 1 solo corre la prueba de ejemplo del esqueleto. La suite completa
llega en la fase 7 con: cálculo de extras e IVA, RN-07 (tope de facturación),
inmutabilidad del tipo de cambio, bitácora no editable y permisos por rol.

## Estructura del proyecto

```
goomaps/
├── docker-compose.yml          # mongo + init + backend + frontend
├── .env.example                # variables opcionales de Compose
├── docs/Gooma.md               # especificación y reglas de negocio
├── docker/
│   ├── mongo/init-replica.sh   # inicializa rs0 (idempotente)
│   ├── php/Dockerfile          # PHP 8.4 + ext. mongodb + Composer
│   ├── php/entrypoint.sh       # .env, APP_KEY, dependencias y arranque
│   └── frontend/Dockerfile     # Node 24 + Vite
├── backend/                    # Laravel 13 (API-only)
│   ├── app/
│   │   ├── Domain/             # reglas de negocio puras (siguientes fases)
│   │   ├── Http/Controllers/Api/V1/
│   │   ├── Models/
│   │   └── Services/
│   ├── config/                 # database.php incluye la conexión mongodb
│   ├── routes/api.php          # /api/v1
│   └── tests/
└── frontend/                   # React + Vite + TypeScript
    └── src/
        ├── lib/api.ts          # cliente HTTP base
        ├── App.tsx             # portada + diagnóstico
        └── App.css             # tema dorado
```

## Roadmap

- [x] **Fase 1.** Docker base (Mongo replica set, backend, frontend) y repositorio.
- [ ] **Fase 2.** Modelo de datos y autenticación con Sanctum (roles RN-19/20).
- [ ] **Fase 3.** Clientes, proyectos, equipo, tarifas por fecha, tecnologías y horas.
- [ ] **Fase 4.** Anticipos, facturas, gastos, costos extra y divisas.
- [ ] **Fase 5.** Bitácora de auditoría y reportes PDF/Excel.
- [ ] **Fase 6.** Mapa del Dinero y portal del cliente.
- [ ] **Fase 7.** Pruebas, seeders y documentación final.

## Capturas

Las capturas del Mapa del Dinero se agregan en la fase 6.

## Solución de problemas

**`port is already allocated`** — cambia `BACKEND_PORT`, `FRONTEND_PORT` o
`MONGO_PORT` en el `.env` de la raíz y vuelve a ejecutar.

**El backend dice que no hay replica set o las transacciones están
deshabilitadas** — revisa `docker compose logs mongo-init`. El backend solo
arranca cuando `mongo-init` termina correctamente; si borraste los volúmenes,
`mongo-init` vuelve a inicializar `rs0` solo.

**Cambié `composer.json` y no se refleja** — `docker compose up --build`. El
volumen `backend_vendor` conserva dependencias entre arranques.

**Permisos en `storage/`** — el entrypoint ajusta permisos en cada arranque; si
persiste, ejecuta `sudo chown -R $USER:$USER backend/storage backend/bootstrap/cache`.

## Equipo

Proyecto final de Ingeniería en Sistemas Computacionales, Instituto Tecnológico
de Milpa Alta II. Integrantes: Gonzalez Gaona Sergio Alfredo, Cruz Hernandez
Isay, Alvarez Cornelio Kevin, De La Rosa Montiel Emanuel y Paredes Muñoz Brayan.
