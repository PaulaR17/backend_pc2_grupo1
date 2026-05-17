# Memoria del Proyecto — EcoTraffic

**Proyecto de Computación — Tercera fase**
**Universidad Europea de Madrid · Doble Grado en Diseño de Videojuegos e Ingeniería Informática · Curso 2025-2026**

| Nombre | Rol |
|---|---|
| Paula Romero Gallart | Project Manager y coordinación |
| Eddy Misael Abisai Catú de León | Responsable LORCA y validación |
| Mahsa Simaei | Analista de datos |
| Mateo Galvis Guayana | Programador Python / ETL |

**Profesor**: Borja Monsalve Piqueras
**Fecha de entrega**: 31 de mayo de 2026

---

## 1. Definición del producto

EcoTraffic es una **plataforma de navegación urbana inteligente para Madrid** que une tres capas:

1. **Datos abiertos de la ciudad** (accidentes, calidad del aire, emergencias, obras, tráfico, clima) recogidos automáticamente mediante scrapers (PC1).
2. **Modelos de Machine Learning** entrenados sobre esos datos, capaces de predecir para los próximos 7 días qué distritos tendrán "incidencia alta" (top 10% del histórico).
3. **Aplicación web** que combina las predicciones con el cálculo de rutas para que el usuario reciba avisos sobre las zonas peligrosas que va a atravesar.

Repositorios:

| Repo | URL |
|---|---|
| PC1 (ML) | https://github.com/MatiuxG/Proyecto-de-Computacion-I (rama PCII) |
| Backend (Laravel) | https://github.com/PaulaR17/backend_pc2_grupo1 |
| Frontend (Angular) | https://github.com/PaulaR17/frontend_pc2_grupo1 |

---

## 2. Arquitectura general

### 2.1 Diagrama de componentes

```
┌──────────────────────────────────────────────────────────────┐
│                       MV LORCA (Docker)                      │
│                                                              │
│  ┌──────────────────┐  ┌──────────────────┐  ┌────────────┐  │
│  │   nginx :80      │  │  Laravel :8000   │  │ PostgreSQL │  │
│  │   ┌────────────┐ │  │  ┌────────────┐  │  │  :5432     │  │
│  │   │ Angular SPA│ │──┤  │ REST API   │  │  │            │  │
│  │   │ (bundle)   │ │  │  └─────┬──────┘  │  │  ┌──────┐  │  │
│  │   └────────────┘ │  │        │         │  │  │users │  │  │
│  └──────────────────┘  │   ┌────▼──────┐  │  │  │preds │  │  │
│                        │   │ PC1 Python│──┼──┼─►│routes│  │  │
│                        │   │ (ML)      │  │  │  │ ...  │  │  │
│                        │   └───────────┘  │  │  └──────┘  │  │
│                        └──────────────────┘  └────────────┘  │
└──────────────────────────────────────────────────────────────┘
                                │
                                ▼ (cron diario y mensual)
                  datos.madrid.es · AEMET · ORS · Informo
```

Los tres servicios viven en contenedores Docker (`pc2_postgres`, `pc2_backend`, `pc2_frontend`) orquestados por `docker-compose.yml`. PC1 está montado como volumen dentro del contenedor del backend, así Laravel puede ejecutar sus scripts vía `exec()` cuando un admin pulsa **"Ejecutar predicciones"** o cuando el cron se dispara.

### 2.2 Casos de uso principales

| Actor | Caso de uso |
|---|---|
| Usuario invitado | Ver mapa con incidencias y predicciones |
| Usuario invitado | Calcular hasta 4 rutas con avisos de zonas peligrosas |
| Usuario registrado | Login, perfil, vehículos, mascota virtual, tienda, historial y favoritos de rutas |
| Admin | Dashboard con métricas y gráficos |
| Admin | CRUD de incidencias |
| Admin | CRUD de usuarios |
| Admin | Ejecutar predicciones (elige modelo y target) |

### 2.3 Diagrama Entidad-Relación (resumen)

Tablas principales en PostgreSQL:

- **users** (id, name, mail, password_hash, rol, status) — autenticación
- **incidents** (id, type, district, description, created_at) — incidencias publicadas por admin
- **predictions** (id, district, for_date, probability, level, model_type, target_type) — output de PC1
- **histories** (id, user_id, origin_lat, origin_lon, dest_lat, dest_lon, distance_km) — historial de rutas
- **favorites** (id, user_id, history_id) — rutas marcadas como favoritas
- **guest_sessions** (session_id, search_count) — control de cuota del invitado
- **pets, items, inventory, equipment, badges, transactions** — gamificación

Relaciones más relevantes:
- `users → histories` (1:N)
- `users → favorites` (1:N)
- `users → pets` (1:1)
- `users → inventory` (1:N) — vía `items`
- `histories ← favorites` (1:N)

Índices clave (para optimizar el rendimiento):
- `predictions.district`, `predictions.for_date`, `predictions.predicted_at`
- `incidents.district`
- `users.mail` (único)

---

## 3. Manual de instalación

El manual completo paso a paso está en `INSTALACION_LORCA.md` dentro del repo del backend. Resumen:

### En LORCA, con Docker

```bash
ssh grupo1@10.151.50.151

mkdir -p /home/grupo1/proyecto
cd /home/grupo1/proyecto

git clone -b PCII https://github.com/MatiuxG/Proyecto-de-Computacion-I.git
git clone https://github.com/PaulaR17/backend_pc2_grupo1.git backend
mkdir -p frontend
git clone https://github.com/PaulaR17/frontend_pc2_grupo1.git frontend/frontend_pc2_grupo1

cd backend
cp .env.example .env
nano .env   # rellenar APP_KEY, JWT_SECRET, ORS_API_KEY, AEMET_API_KEY

docker compose up -d --build
```

La primera vez tarda **15-20 min** (descarga imágenes base + builds + instalación de dependencias Python en el venv).

Las migraciones y el seeder se aplican automáticamente al arrancar el backend. Para tener predicciones desde el primer momento:

```bash
docker compose exec backend /opt/pc1-venv/bin/python /opt/pc1/generar_predicciones.py --dias 7 --target Accidentes
```

### Crons (en LORCA)

```bash
crontab -e
```

Pegar:

```cron
0 4 * * * docker compose -f /home/grupo1/proyecto/backend/docker-compose.yml exec -T backend /opt/pc1-venv/bin/python /opt/pc1/generar_predicciones.py --dias 7 --target Accidentes >> /home/grupo1/proyecto/logs/predict.log 2>&1
0 3 1 * * docker compose -f /home/grupo1/proyecto/backend/docker-compose.yml exec -T backend /opt/pc1-venv/bin/python /opt/pc1/run_all.py >> /home/grupo1/proyecto/logs/retrain.log 2>&1
```

---

## 4. Manual de pruebas

### 4.1 Credenciales de prueba (creadas por `db:seed`)

| Email | Contraseña | Rol |
|---|---|---|
| admin@ecotraffic.com | password123 | ADMIN |
| usuario@ecotraffic.com | password123 | USER |
| paula@ecotraffic.com | password123 | USER |

### 4.2 Pruebas funcionales mínimas

**Acceso público** (sin login):
1. Abrir `http://10.151.50.151` (o `http://localhost:8080` en local).
2. El mapa carga centrado en Madrid con marcadores de incidencias.
3. Pulsar **"Ver predicciones"** → aparecen 21 círculos coloreados (verde BAJO, naranja MEDIO, rojo ALTO).
4. Pulsar **"Ver puntos de interés"** → aparecen marcadores azules con POIs cercanos.
5. Buscar un destino y calcular ruta → aparece notificación con el aviso de las zonas peligrosas atravesadas (si las hay).
6. Tras 4 búsquedas, la 5ª saca un modal pidiendo registro.

**Usuario registrado**:
1. Login con `usuario@ecotraffic.com / password123`.
2. Sidebar con: Inicio, Historial/Favoritos, Mis vehículos, Mi mascota, Tienda, Ayuda.
3. Editar perfil → cambiar nombre, ver chapitas.
4. Vehículos → CRUD funcional.
5. Mascota virtual → XP visible.
6. Tienda → comprar items con chapitas; aparecen en inventario.

**Administrador**:
1. Login con `admin@ecotraffic.com / password123`.
2. Dashboard → 3 gráficos Chart.js (usuarios, tipos incidencia, evolución).
3. Selectores Modelo (Random Forest / Decision Tree / SVM) + Target (Accidentes / Calidad Aire / Emergencias) + Días.
4. Pulsar **"Ejecutar predicciones"** → mensaje de éxito tras ~1-2 min.
5. Gestionar incidencias → CRUD completo.
6. Gestionar usuarios → listado, edición de rol, desactivación.

### 4.3 Pruebas técnicas (API)

```bash
#login y guardar token
TOKEN=$(curl -s -X POST http://10.151.50.151:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"mail":"admin@ecotraffic.com","password":"password123"}' \
  | jq -r .access_token)

#test endpoints admin con JWT
curl -H "Authorization: Bearer $TOKEN" http://10.151.50.151:8000/api/admin/users
curl -H "Authorization: Bearer $TOKEN" http://10.151.50.151:8000/api/admin/dashboard

#test ruta con risk_zones (no requiere auth)
curl -X POST "http://10.151.50.151:8000/api/routes/preview?include=summary" \
  -H "Content-Type: application/json" \
  -d '{"origin":{"lat":40.4153,"lon":-3.7074},"destination":{"lat":40.4014,"lon":-3.6076}}'
```

---

## 5. Aspectos destacables del desarrollo

### 5.1 Refactorización completa de PC1

PC1 al inicio del curso tenía varios fallos conceptuales (target binarizado por la mediana, split aleatorio en lugar de temporal, métricas de regresión sobre un problema de clasificación) y la mayoría de los scrapers no producían datos por una vieja URL del portal de Madrid que ya no expone enlaces. La tercera fase incluyó:

- Migración de todos los scrapers (Accidentes, Calidad del Aire, Emergencias, Obras, Tráfico) a la vista CKAN `/dataset/<id>/downloads` del nuevo portal.
- Granularidad **horaria** donde la fuente lo permite (Accidentes y SAMUR sí tienen columna hora; Bomberos y obras solo mensual, se replican en las 24 horas del día).
- Reescritura del scraper de tráfico para que descargue automáticamente los ZIPs históricos del dataset 208627 (uno por mes, ~90 MB cada uno), procesarlos por chunks de 200.000 filas con pandas y agregar por sensor-hora.
- Nuevo dataset unificado con **831.124 filas × 22 columnas**, balanceado al 10% para los tres targets.
- Modelos entrenados con `TimeSeriesSplit + GridSearchCV`, métricas correctas (accuracy, precision, recall, F1, ROC-AUC), y SVM cambiado de `SVC(rbf)` a `LinearSVC + CalibratedClassifierCV` para que escale a 800k filas.

ROC-AUC final con dataset horario:

| Modelo | ROC-AUC | F1 (top-10%) |
|---|---:|---:|
| Random Forest | 0.749 | 0.080 |
| Decision Tree | 0.539 | 0.062 |
| LinearSVC | 0.677 | 0.000 |

### 5.2 Integración predicción ↔ ruta (factor diferencial)

Cuando el usuario pide una ruta, el backend muestrea 12 puntos a lo largo de la línea recta origen-destino y los asigna al distrito del centroide más cercano. Luego consulta la tabla `predictions` por esos distritos y devuelve la lista `risk_zones` con el nivel más alto encontrado para cada uno. El frontend muestra una notificación amarilla cuando hay distritos con nivel MEDIO o roja cuando hay ALTO.

Es una aproximación (la ruta real de ORS puede desviarse de la recta), pero **detecta correctamente todos los distritos del recorrido en una ciudad pequeña como Madrid** y permite que el sistema cumpla con su propuesta de valor: las predicciones de PC1 informan la decisión del usuario.

La lógica vive en un trait PHP `HasRiskZones` reutilizado por dos controladores distintos (usuario logueado y guest), para evitar duplicación.

### 5.3 Estilo de código didáctico (criterio personal del equipo)

El proyecto sigue un estilo de código muy consistente en los tres lenguajes (Python, PHP, TypeScript):

- **Un único `return` al final** de cada función, construyendo una variable de resultado a lo largo del flujo.
- **Sin `break`, `continue` ni returns intermedios** dentro de bucles o condicionales.
- **Comentarios pegados al `#` o `//`** (sin espacio), en minúsculas salvo nombres propios y acrónimos.
- Cabecera de función corta (1-2 líneas) con el QUÉ HACE; dentro del cuerpo solo se comenta lo no obvio.
- Sin sobrecarga: nada de "esta línea asigna 5 a x".

Se han refactorizado ~55 archivos con estas reglas, eliminando alrededor de 50 returns intermedios y todos los breaks/continues. El objetivo es que el código se lea como una pequeña historia, sin saltos.

### 5.4 Dockerización completa

Cada repo tiene su propio `Dockerfile`. El backend tiene además un `docker-compose.yml` que orquesta los tres servicios + PostgreSQL. PC1 se monta como volumen dentro del backend para que Laravel pueda invocarlo directamente con `exec()`, sin SSH ni comunicación entre contenedores.

El `docker-entrypoint.sh` del backend:
- Espera a que PostgreSQL esté listo (con healthcheck).
- Crea el venv de PC1 en `/opt/pc1-venv` (volumen named para que persista entre `up --build`).
- Aplica las migraciones y el seeder solo la primera vez (marcador `.installed`).

### 5.5 Seguridad

- Autenticación con JWT (RS512) firmado con claves RSA 4096.
- Middleware `Admin` que verifica el rol en cada endpoint `/admin/*`.
- Secretos (`APP_KEY`, `JWT_SECRET`, `ORS_API_KEY`, `AEMET_API_KEY`) en `.env` local, NUNCA en git. El `docker-compose.yml` los lee con `${VAR}` desde `.env`.
- CORS abierto a `*` para desarrollo; en producción conviene limitarlo al dominio de LORCA.

### 5.6 Problemas que aparecieron durante el desarrollo

| Problema | Solución |
|---|---|
| Scrapers vacíos por URL antigua del portal Madrid | Migrar a vista CKAN `/dataset/<id>/downloads` |
| Modelo Random Forest de 2.6 GB | Limitar `max_depth=15` y usar `joblib.dump(compress=3)` → 9.3 MB |
| SVM con RBF no escalaba a 800k filas | Cambiar a `LinearSVC` + calibración para mantener `predict_proba` |
| `composer install` fallaba con `php:8.2-cli` | Subir imagen a `php:8.4-cli` (Symfony 8 lo exige) |
| Puerto 80 ocupado en máquina de desarrollo | Mapear frontend a `8080:80` |
| `php artisan key:generate` fallaba en Docker sin `.env` | Pasar `APP_KEY` y `JWT_SECRET` como env vars del contenedor |
| Tabla `cache` no existía y Laravel intentaba usarla | `CACHE_DRIVER=file` en `docker-compose.yml` |
| `.venv` de PC1 con paths del host (`pyenv`) | Mover venv a `/opt/pc1-venv` (fuera del volumen) y persistirlo en otro volumen named |
| GitGuardian detectó secretos en commit público | Rotar `APP_KEY` y `JWT_SECRET`, sacarlos del compose y leerlos del `.env` |

---

## 6. Reparto del trabajo

- **Paula**: coordinación, integración Docker, refactor de estilo, fix de seguridad GitGuardian, conexión frontend ↔ backend, integración predicción ↔ ruta.
- **Eddy**: despliegue en LORCA, configuración PostgreSQL, validación remota.
- **Mahsa**: análisis y validación del dataset unificado, métricas de los modelos.
- **Mateo**: scrapers de PC1, pipeline ETL, entrenamiento ML.

---

## 7. Futuras mejoras

- Decodificar la geometría real de ORS (polyline encoded) para detectar los distritos atravesados con precisión, en lugar de aproximar por la línea recta.
- Usar `avoid_polygons` de ORS para que la ruta esquive los distritos con predicción ALTO.
- Mostrar los `risk_zones` también de forma visual sobre el mapa (no solo en notificación).
- Itinerarios inteligentes: integrar la capa POIs con las rutas ("qué hacer en B").
- Tramos de calle específicos para usuario registrado (más granular que distrito).
- Tests automatizados (PHPUnit en backend, Jasmine en frontend, pytest en PC1).
