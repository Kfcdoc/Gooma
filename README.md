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

> **Estado actual: Fase 2 completada.** Infraestructura Docker, autenticación con
> Sanctum sobre MongoDB, roles (administrador, gestor, cliente), modelo de datos
> de proyectos con `manager_id`, catálogo de roles y tarifas, y seeder que cuadra
> exacto con el presupuesto de la sección 1.7. Los endpoints de negocio se agregan
> en las siguientes fases (ver [Roadmap](#roadmap)).

---

## Stack

| Capa | Tecnología |
|---|---|
| Backend | Laravel 13 · API REST en `/api/v1` |
| Base de datos | MongoDB 8 como **replica set de un nodo** (transacciones) |
| Driver | `mongodb/laravel-mongodb` + extensión PHP `mongodb` |
| Frontend | React 19 + Vite 8 + TypeScript |
| Autenticación | Laravel Sanctum con tokens Bearer (guardados en MongoDB) |
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
                                                          │  localhost:27019    │
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
rápidas. Las migraciones e índices de MongoDB se aplican solos al arrancar el
backend.

Con los contenedores arriba, carga el catálogo de roles/tarifas, los usuarios,
el cliente y el proyecto demo:

```bash
docker compose exec backend php artisan migrate --seed
```

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

## API v1

La API es stateless: `POST /api/v1/auth/login` devuelve un token Bearer que debe
enviarse en `Authorization: Bearer <token>`. Los tokens se guardan **hasheados**
en la colección `personal_access_tokens` de MongoDB.

| Método | Ruta | Acceso |
|---|---|---|
| `GET` | `/api/v1/health` | Público |
| `POST` | `/api/v1/auth/login` | Público (máx. 6 intentos/minuto) |
| `POST` | `/api/v1/auth/logout` | Autenticado (revoca el token actual) |
| `POST` | `/api/v1/auth/logout-all` | Autenticado (revoca todas las sesiones) |
| `GET` | `/api/v1/auth/me` | Autenticado |
| `GET/POST` | `/api/v1/users` | Solo administrador |
| `GET/PUT/DELETE` | `/api/v1/users/{id}` | Solo administrador |

```bash
# Iniciar sesión
curl -s -X POST http://localhost:8000/api/v1/auth/login \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{"email":"admin@goomaps.local","password":"password"}'

# Consultar el perfil con el token recibido
curl -s http://localhost:8000/api/v1/auth/me \
  -H "Authorization: Bearer <token>" -H 'Accept: application/json'
```

Las respuestas de recursos usan el envoltorio `data` de Laravel; el login
devuelve directamente `{ token, token_type, user }`. Los errores de validación
llegan con HTTP 422, la falta de sesión con 401 y los permisos insuficientes
con 403.

## Modelo de datos y decisiones

Colecciones de MongoDB (los índices se crean con `migrate`):

| Colección | Contenido |
|---|---|
| `users` | Cuentas con rol de acceso `admin` / `gestor` / `cliente` (RN-19). |
| `clients` | Clientes contratantes; el usuario cliente se vincula por `client_id`. |
| `personal_access_tokens` | Tokens Sanctum hasheados, en MongoDB. |
| `roles` | Catálogo de **roles de trabajo** (RN-03/RN-04), distinto de los roles de acceso. |
| `role_rates` | Tarifa por hora, divisa y vigencia (`effective_from` / `effective_to`). |
| `projects` | `client_id`, `manager_id`, snapshot de `budget_lines`, `extras` y `budget`. |

RN-01 se cumple con `projects.manager_id`; `Project::isVisibleBy()` ya aplica
RN-20 (administrador ve todo, gestor ve los suyos, cliente ve el suyo). Los
montos se guardan en centavos enteros (`*_cents`) para que los totales cuadren
sin error de coma flotante.

### Presupuesto demo (sección 1.7)

| Concepto | Cálculo | MXN |
|---|---|---:|
| Costo base | 600 h × tarifas por rol | 227,000.00 |
| Imprevistos | 10% del costo base, necesario | 22,700.00 |
| Riesgo tecnológico | 5% del costo base, opcional activo | 11,350.00 |
| Margen de ganancia | 15% del costo base, necesario | 34,050.00 |
| Subtotal | Costo base + extras | 295,100.00 |
| IVA | 16% del subtotal, necesario | 47,216.00 |
| **Total** | | **342,316.00** |
| Sin riesgo opcional | Escenario alterno de 6.3.4 | 329,150.00 |

Decisiones que también quedan registradas para las siguientes fases:

- El margen del 15% es una **línea de extras** sobre el costo base (RN-15), no
  un margen calculado sobre ventas.
- Saldo facturable = `total − anticipos aplicados − facturas no canceladas`
  (se aplicará en la fase 4).
- El Mapa del Dinero usa la **tasa vigente**; cada movimiento guarda su propio
  **snapshot del tipo de cambio** para reportes (fases 4 y 6).
- Las facturas solo tienen dos estados: `emitida` o `cancelada`. Una factura
  emitida no se edita; se cancela con motivo y se genera otra (RN-09).
- Los gastos no generan IVA acreditable y no afectan el margen (fase 4).
- El cliente nunca ve tarifas individuales por persona, solo montos agregados
  (RN-21), cuando se construya el portal en la fase 6.

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

Se crean con `docker compose exec backend php artisan migrate --seed`:

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | `admin@goomaps.local` | `password` |
| Gestor | `gestor@goomaps.local` | `password` |
| Cliente | `cliente@goomaps.local` | `password` |

El cliente demo queda vinculado a **Constructora del Valle** y el gestor demo
es el responsable (`manager_id`) del proyecto `GOOMAPS-2026`. El seeder es
idempotente: puede ejecutarse varias veces sin duplicar cuentas, roles ni
proyectos, y sin cambiar contraseñas existentes.

## Pruebas

```bash
docker compose exec backend php artisan test
```

La fase 2 cubre 20 pruebas: login, credenciales inválidas, usuarios
desactivados, `/me`, revocación de tokens, RBAC por rol (admin/gestor/cliente),
validación de usuarios, hash de contraseñas, seeder idempotente, catálogo de
tarifas con historial (RN-04), presupuesto exacto de la sección 1.7 y el
diagnóstico de infraestructura. Las pruebas usan la base `goomaps_test` y
jamás tocan `goomaps`; la suite completa de negocio se amplía en la fase 7.

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
│   │   ├── Domain/Costs/       # BudgetCalculator (RN-15, montos en centavos)
│   │   ├── Http/
│   │   │   ├── Controllers/Api/V1/  # health, auth y usuarios
│   │   │   ├── Middleware/     # role y active
│   │   │   ├── Requests/       # validación de login y usuarios
│   │   │   └── Resources/      # representación JSON de modelos
│   │   ├── Models/             # User, Client, Role, RoleRate, Project, tokens (MongoDB)
│   │   └── Services/           # orquestación (siguientes fases)
│   ├── config/                 # database.php incluye la conexión mongodb
│   ├── database/
│   │   ├── migrations/         # colecciones e índices de MongoDB
│   │   ├── factories/          # datos falsos para pruebas
│   │   └── seeders/            # CatalogSeeder + DemoSeeder (presupuesto 1.7)
│   ├── routes/api.php          # /api/v1
│   └── tests/                  # Unit + Feature (base goomaps_test)
└── frontend/                   # React + Vite + TypeScript
    └── src/
        ├── lib/api.ts          # cliente HTTP base
        ├── App.tsx             # portada + diagnóstico
        └── App.css             # tema dorado
```

## Roadmap

- [x] **Fase 1.** Docker base (Mongo replica set, backend, frontend) y repositorio.
- [x] **Fase 2.** Modelo MongoDB (proyectos con `manager_id`, catálogo
  `roles`/`role_rates`), autenticación Sanctum, roles RN-19/20 y seeder exacto
  de la sección 1.7.
- [ ] **Fase 3.** Endpoints de clientes, proyectos, equipo, tarifas vigentes,
  tecnologías y horas.
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
entrypoint sincroniza `vendor` con `composer.lock` en cada arranque; si algo
quedó corrupto, `docker compose down -v` y vuelve a levantar.

**Permisos en `storage/`** — el entrypoint ajusta permisos en cada arranque; si
persiste, ejecuta `sudo chown -R $USER:$USER backend/storage backend/bootstrap/cache`.

## Equipo

Proyecto final de Ingeniería en Sistemas Computacionales, Instituto Tecnológico
de Milpa Alta II. Integrantes: Gonzalez Gaona Sergio Alfredo, Cruz Hernandez
Isay, Alvarez Cornelio Kevin, De La Rosa Montiel Emanuel y Paredes Muñoz Brayan.
