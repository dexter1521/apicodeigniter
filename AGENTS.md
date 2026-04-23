# AGENTS.md - Documentación Avanzada de Agentes en el Proyecto API CodeIgniter

## Introducción
Este documento describe los "agentes" o componentes principales del proyecto API CodeIgniter, basados en un análisis exhaustivo desde la perspectiva de un desarrollador senior en APIs REST. El proyecto, aunque ya no se utiliza activamente, sirvió como base para múltiples implementaciones y ahora se eleva a un estado óptimo. Los agentes se conceptualizan como módulos autónomos (controladores, modelos, librerías) que procesan solicitudes REST de manera modular, segura y escalable, siguiendo principios SOLID, DRY y mejores prácticas de CI3.

Enfoque senior: Priorizamos seguridad (JWT robusto, rate limiting), escalabilidad (caché, paginación), mantenibilidad (herencia, inyección de dependencias simulada) y testing. Incluimos recomendaciones avanzadas para optimizar el proyecto en CI3.

## Arquitectura General y Principios REST
- **Arquitectura**: MVC extendida con agentes especializados. Controladores actúan como "agentes de negocio" (manejan lógica), modelos como "agentes de datos" (interacciones BD), librerías como "agentes de utilidades".
- **Principios REST**: Stateless, recursos identificables (e.g., `/clientes/{id}`), métodos HTTP semánticos, respuestas JSON estandarizadas (HATEOAS opcional).
- **Limitaciones CI3**: Sin built-in para middlewares o inyección de dependencias; usamos herencia y hooks para simular.
- **Mejores Prácticas Senior**:
  - Versionado de API (e.g., `/v1/clientes`).
  - Uso de HTTP status codes apropiados (200 OK, 201 Created, 401 Unauthorized).
  - Evitar lógica de negocio en controladores; delegar a servicios (simulados via helpers).

## Agentes Principales

### 1. Agente de Autenticación (Auth Controller)
- **Archivo**: `application/controllers/Auth.php`
- **Funcionalidad**: Maneja autenticación JWT completa, incluyendo login, logout, refresh tokens y revocación.
- **Endpoints**:
  - `POST /auth/login`: Valida credenciales, genera access + refresh tokens.
  - `POST /auth/logout`: Revoca sesión (placeholder para implementación persistente).
  - `POST /auth/refresh`: Renueva access token via refresh token.
- **Tecnologías**: `firebase/php-jwt` para tokens seguros con claims (`sub`, `email`, `admin`).
- **Librerías de soporte**: `Jwt_auth` para validación JWT y `Token_service` para refresh/logout.
- **Configuración**: `application/config/jwt.php` con fallback a env vars; expiración configurable.
- **Ejemplo de Código (Login)**:
  ```php
  public function login_post() {
      $payload = $this->parseJsonBody();
      // Validaciones con form_validation
      if ($this->form_validation->run() === false) {
          return $this->sendValidationResponse($this->form_validation->error_array());
      }
      // Generar token con claims
      $claims = ['sub' => $user['id'], 'email' => $user['correo'], 'admin' => $user['admin']];
      $token = authorization::generateToken($claims, ['expiration' => 3600]);
      return $this->sendSuccessResponse(['access_token' => $token, 'refresh_token' => $refresh]);
  }
  ```
- **Mejores Prácticas Senior**: Implementar revocación persistente (e.g., blacklist en BD/Redis). Usar refresh tokens para sesiones largas. Validar claims en cada request.
- **Seguridad**: Evitar almacenar passwords en plain; usar hashing (bcrypt). Rate limiting en login para prevenir brute force.
- **Arquitectura extendida**: separar la verificación JWT (`Jwt_auth`) del ciclo de vida de tokens (`Token_service`) mejora mantenibilidad.

### 2. Agente de Clientes (Clientes Controller)
- **Archivo**: `application/controllers/Clientes.php`
- **Funcionalidad**: CRUD completo para entidad "clientes" con paginación, búsqueda y soft delete.
- **Endpoints**:
  - `GET /clientes/lista?page=1&search=query`: Lista paginada con filtros.
  - `GET /clientes/detalle/{id}`: Detalles específicos.
  - `POST /clientes/crear`: Creación con validaciones.
  - `PUT /clientes/actualizar/{id}`: Actualización.
  - `DELETE /clientes/eliminar/{id}`: Soft delete.
- **Modelo Asociado**: `Clientes_model.php` (extiende `General_model`).
- **Validaciones**: Emails únicos, campos requeridos; usa `form_validation`.
- **Ejemplo de Código (Lista)**:
  ```php
  public function lista_get() {
      $page = $this->get('page') ?? 1;
      $search = $this->get('search');
      $result = $this->Clientes_model->getAll($page, $search);
      return $this->sendSuccessResponse($result);
  }
  ```
- **Mejores Prácticas Senior**: Implementar HATEOAS (links en respuestas). Usar ETags para caching. Paginación eficiente (OFFSET/LIMIT con índices).
- **Escalabilidad**: Caché en BD para queries frecuentes. Logging de operaciones críticas.

### 3. Agente General (General Model)
- **Archivo**: `application/models/General_model.php`
- **Funcionalidad**: Base abstracta para CRUD genérico, con soporte para soft delete y queries dinámicas.
- **Métodos**: `insert($data)`, `update($id, $data)`, `delete($id)`, `select($where, $limit)`.
- **Uso**: Extendido por modelos específicos; promueve DRY.
- **Ejemplo de Código**:
  ```php
  public function insert($data) {
      $this->db->insert($this->table, $data);
      return $this->db->insert_id();
  }
  ```
- **Mejores Prácticas Senior**: Usar transacciones para operaciones críticas. Implementar eager loading para relaciones. Evitar N+1 queries.

### 4. Agente de Respuestas (IResponse Library)
- **Archivo**: `application/libraries/IResponse.php`
- **Funcionalidad**: Estandariza respuestas JSON con estructura fija.
- **Estructura**: `{"status": "success/error", "message": "", "data": {}, "errors": []}`.
- **Uso**: Métodos como `sendSuccessResponse($data)`, `sendErrorResponse($msg, $code)`.
- **Mejores Prácticas Senior**: Incluir metadata (e.g., timestamps, request ID). Soporte para internacionalización en mensajes.

### 5. Agente JWT de Servicio (Jwt_auth)
- **Archivo**: `application/libraries/Jwt_auth.php`
- **Funcionalidad**: Valida JWT firmados, comprueba revocación y usuarios activos.
- **Uso**: `->validate($token)` desde `MY_Controller`.
- **Ventaja**: mantiene `MY_Controller` limpio y separa la lógica de validación JWT.

### 6. Agente de Ciclo de Vida de Tokens (Token_service)
- **Archivo**: `application/libraries/Token_service.php`
- **Funcionalidad**: Genera refresh tokens, valida refresh tokens, revoca refresh y revoca access tokens.
- **Uso**: `Auth.php` delega emisión y revocación a este servicio.
- **Mejores Prácticas Senior**: Centralizar la lógica de refresh/revoke evita duplicar reglas de negocio.

### 8. Agente JWT (JWT Helper)
- **Archivo**: `application/helpers/jwt_helper.php`
- **Funcionalidad**: Utilitarios para encode/decode JWT.
- **Funciones**: `jwt_encode($payload)`, `jwt_decode($token)`.
- **Dependencias**: `firebase/php-jwt`.
- **Mejores Prácticas Senior**: Validar issuer/audience. Usar RS256 en producción (asimétrico).

- **Archivo**: `application/helpers/jwt_helper.php`
- **Funcionalidad**: Utilitarios para encode/decode JWT.
- **Funciones**: `jwt_encode($payload)`, `jwt_decode($token)`.
- **Dependencias**: `firebase/php-jwt`.
- **Mejores Prácticas Senior**: Validar issuer/audience. Usar RS256 en producción (asimétrico).

## Configuración de Agentes
- **Base Controller**: `application/core/MY_Controller.php` - Extiende CI_Controller; incluye `requireAuthentication()`, helpers de respuesta.
- **Configuraciones**: `rest.php` (endpoints, CORS), `database.php` (BD), `autoload.php` (carga automática).
- **Mejores Prácticas Senior**: Usar env vars para secrets. Configurar CORS restrictivo. Implementar rate limiting via hooks.

## Seguridad Avanzada
- **JWT**: Claims robustos, expiración corta, refresh tokens.
- **Rate Limiting**: Configurar en `rest.php` (`rest_limits_method`).
- **CORS**: Reglas explícitas por origen.
- **Validaciones**: Sanitización de inputs; evitar SQL injection via Active Record.
- **Logging**: Usar `log_message()` para auditoría; integrar con herramientas externas (e.g., ELK stack).

## Escalabilidad y Rendimiento
- **Caché**: Implementar en modelos (e.g., Redis via librería externa).
- **Paginación**: Eficiente con LIMIT/OFFSET.
- **BD**: Índices en campos de búsqueda; usar InnoDB.
- **Asincronía**: Simular con queues (e.g., via cron jobs).

## Testing y QA
- **Unit Tests**: Usar PHPUnit para modelos/controladores.
- **Integration Tests**: Postman/Newman para endpoints.
- **Cobertura**: Apuntar a >80% en agentes críticos.
- **Mejores Prácticas Senior**: Tests automatizados en CI/CD; mocks para BD.

## Documentación y Mantenimiento
- **Swagger**: Generado automáticamente en `/docs`.
- **PHPDoc**: Completo en todos los agentes.
- **Versionado**: Etiquetar releases; mantener changelog.

## Mejoras Sugeridas para Agentes (Desde MEJORAS.md)
- **Completadas**: Integración `firebase/php-jwt`, helpers en `MY_Controller`, validaciones robustas.
- **Pendientes**:
  - Endurecer `rest.php`: HTTPS por entorno, rate limiting, CORS.
  - Migraciones para `usuarios`; tests con CIUnit/Postman.
  - Caché en modelos; logging avanzado.
- **Senior Recomendaciones**: Optimizar CI3 con librerías externas (e.g., Redis para caché). Implementar OAuth2 si escala. Monitoreo con APM (e.g., New Relic).

## Conclusión
Este proyecto, elevado a estándares senior, sirve como plantilla robusta para APIs REST en CI3. Los agentes son modulares y seguros, listos para producción con ajustes. Para más detalles, consulta `README.md`, `MEJORAS.md` y `GUIA_DE_USO.md`.

*Expandido y optimizado para un enfoque senior en APIs REST con CodeIgniter 3.*
