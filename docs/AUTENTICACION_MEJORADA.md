# MEJORAS DE SEGURIDAD Y AUTENTICACIÓN - Resumen de Implementación

## Descripción General
Se ha implementado un sistema de autenticación robusto y escalable que soporta tanto **usuarios con JWT** como **aplicaciones de terceros con tokens estáticos (API Keys)**, con mejoras significativas en seguridad, rate limiting, revocación de tokens y logging.

---

## 1. Nuevas Tablas de Base de Datos

### token_blacklist
Almacena tokens revocados (JWT) para prevenir su reutilización tras logout o compromisos.
```sql
CREATE TABLE token_blacklist (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    token_jti VARCHAR(255) NOT NULL UNIQUE,
    token_hash VARCHAR(255) NOT NULL UNIQUE,
    user_id BIGINT UNSIGNED NOT NULL,
    revoked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reason VARCHAR(255) NULL,
    expires_at TIMESTAMP NOT NULL
);
```

### refresh_tokens
Almacena refresh tokens con capacidad de revocación individual.
```sql
CREATE TABLE refresh_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash VARCHAR(255) NOT NULL UNIQUE,
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    revoked_at TIMESTAMP NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL
);
```

### api_tokens (Mejorada)
Tabla existente mejorada con hash, rate limiting y tracking.
```sql
ALTER TABLE api_tokens ADD COLUMN (
    token_hash VARCHAR(255) NOT NULL UNIQUE,
    rate_limit INT DEFAULT 1000,
    last_used_at TIMESTAMP NULL,
    created_by BIGINT UNSIGNED NULL
);
```

### rate_limit_logs
Tracking de intentos para prevenir brute force y control de velocidad.
```sql
CREATE TABLE rate_limit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(255) NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    request_count INT DEFAULT 1,
    reset_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_limit (identifier, endpoint, reset_at)
);
```

---

## 2. Nueva Librería: RateLimit

**Archivo**: `application/libraries/RateLimit.php`

Centraliza el control de rate limiting para:
- Login (prevención de brute force)
- Endpoints específicos
- Tokens estáticos (API keys)

**Métodos principales**:
```php
$this->load->library('RateLimit');

// Verificar límite
if (!$this->ratelimit->checkLimit($identifier, $endpoint, $maxAttempts, $windowSeconds)) {
    // Excedido
}

// Obtener información
$info = $this->ratelimit->getInfo($identifier, $endpoint, $maxAttempts);
// Retorna: ['current', 'limit', 'reset_at', 'remaining']

// Limpiar registros expirados
$this->ratelimit->cleanup();
```

---

## 3. Mejoras en Authorization Helper

**Archivo**: `application/helpers/authorization_helper.php`

### Nuevas Funcionalidades:
- **Blacklist de Tokens**: Revocación permanente de JWT
- **Refresh Tokens**: Sistema completo de renovación
- **Mejor Logging**: Sin loggear tokens completos, solo IDs
- **Validación de Blacklist**: En cada validación de token
- **Decodificación Segura**: Sin validar firma (solo para refresh)

### Métodos Nuevos:
```php
// Generar y validar refresh tokens
authorization::issueRefreshToken($userClaims);
authorization::validateRefreshToken($tokenString, $userId);
authorization::revokeRefreshToken($tokenString, $userId);

// Revocación de JWT
authorization::revokeToken($token, $reason);
authorization::isTokenBlacklisted($jti);

// Decodificación sin validación (solo para refresh)
authorization::decodeTokenWithoutValidation($token);
```

---

## 4. Mejoras en Auth Controller

**Archivo**: `application/controllers/Auth.php`

### Rate Limiting en Login
```php
const LOGIN_RATE_LIMIT = 5;        // 5 intentos máximo
const LOGIN_RATE_WINDOW = 900;     // ventana de 15 minutos
```

### Flujo de Login Mejorado
1. Verificar rate limit por IP
2. Validar credenciales
3. Generar access token JWT
4. Generar y almacenar refresh token
5. Loggear el evento

**Respuesta**:
```json
{
  "status": 200,
  "success": true,
  "response": {
    "token_type": "Bearer",
    "access_token": "eyJ0eXAi...",
    "refresh_token": "abc123...",
    "expires_in": 3600,
    "issued_at": 1704067200
  },
  "messages": ["Autenticación exitosa"],
  "timestamp": 1704067200,
  "request_id": "req_abc123..."
}
```

### Logout Mejorado
- Revoca el token actual en blacklist
- Loggea el evento

### Refresh Token Endpoint
- Requiere access token expirado + refresh token
- Valida refresh token en BD
- Genera nuevo access token
- No requiere re-autenticación

---

## 5. Mejoras en MY_Controller

**Archivo**: `application/core/MY_Controller.php`

### Validación de Tokens Estáticos Mejorada
- Usa hash para búsqueda (si está disponible)
- Verifica expiración
- Aplica rate limiting por app
- Loggea accesos

### Manejo de Rate Limiting
- Verifica límites por IP para usuarios
- Verifica límites por app para tokens estáticos
- Retorna información de reintentos

---

## 6. Flujo Completo de Autenticación

### Para Usuarios (JWT):
```
1. POST /auth/login {usuario, contrasenia}
   ↓
2. Verificar rate limit (IP)
   ↓
3. Validar credenciales en BD
   ↓
4. Generar JWT con claims (sub, email, admin, etc.)
   ↓
5. Generar refresh token (hash en BD)
   ↓
6. Responder con ambos tokens
   ↓
7. Cliente guarda tokens localmente

8. Request a endpoint protegido con "Authorization: Bearer <access_token>"
   ↓
9. MY_Controller valida JWT:
   - Verifica firma
   - Verifica expiración
   - Verifica en blacklist
   - Valida claims obligatorios (sub, jti)
   - Verifica usuario activo en BD
   ↓
10. Si válido → request continúa
11. Si inválido → error 401/403

12. POST /auth/refresh {refresh_token}
    (con "Authorization: Bearer <access_token_expirado>")
    ↓
13. Decodificar access token sin validación
    ↓
14. Validar refresh token en BD
    ↓
15. Generar nuevo access token
    ↓
16. Responder con nuevo token

17. POST /auth/logout (con access_token)
    ↓
18. Revocar token en blacklist
    ↓
19. Sesión terminada
```

### Para Apps Terceros (API Keys):
```
1. App obtiene token estático (x-api-key) del admin
   ↓
2. Request con "X-Api-Key: <token>"
   ↓
3. MY_Controller valida:
   - Token existe en api_tokens
   - Token activo
   - No expirado
   - Rate limit no excedido
   ↓
4. Si válido → request continúa
5. Si inválido → error 401/403

6. Tracking:
   - Actualiza last_used_at
   - Registra en rate_limit_logs
```

---

## 7. Mejoras en Seguridad

| Aspecto | Mejora |
|---------|--------|
| **Revocación** | Blacklist en BD, logout inmediato |
| **Rate Limiting** | Por IP (usuarios), por app (terceros), por endpoint |
| **Logging** | Sin loggear tokens, solo IDs y eventos |
| **Refresh Tokens** | Almacenados con hash, revocables, con TTL |
| **API Keys** | Rate limit configurable, tracking de uso |
| **Validación** | Claims obligatorios (sub, jti), user status check |
| **HTTPS** | Recomendado forzar en producción |
| **Hashing** | SHA256 para tokens en BD (cuando aplique) |

---

## 8. Configuración Necesaria

### jwt.php
```php
$config['jwt_key'] = $_ENV['JWT_KEY'] ?? 'your-secret-key';
$config['jwt_algorithm'] = 'HS256';
$config['token_expire_time'] = 3600;           // 1 hora
$config['jwt_issuer'] = 'api.example.com';
$config['jwt_audience'] = 'web-app';
$config['jwt_nbf_offset'] = 0;
$config['jwt_leeway'] = 10;                    // 10 segundos
```

### Autoload
Añadir a `config/autoload.php`:
```php
$autoload['libraries'] = array('RateLimit');
$autoload['helpers'] = array('jwt', 'authorization');
```

---

## 9. Migraciones SQL

Ejecutar el script en:
```
database/migrations/002_create_token_blacklist_table.sql
```

---

## 10. Consideraciones de Producción

1. **HTTPS Obligatorio**: Forzar redirección HTTPS
2. **CORS**: Restringir orígenes en `rest.php`
3. **Rate Limiting**: Ajustar límites según tráfico esperado
4. **Cleanup**: Ejecutar `RateLimit::cleanup()` periódicamente (cron)
5. **Logging**: Integrar con ELK Stack o similar
6. **Monitoring**: APM como New Relic
7. **Secrets**: Usar variables de entorno (.env)
8. **Token Storage**: Clientes deben usar localStorage/sessionStorage seguro
9. **CSRF**: Si también sirve HTML, implementar CSRF tokens

---

## 11. Testing

Ejemplos de pruebas recomendadas:

```bash
# Login
curl -X POST http://localhost:8000/auth/login \
  -H "Content-Type: application/json" \
  -d '{"usuario": "test@example.com", "contrasenia": "password123"}'

# Usar token
curl -X GET http://localhost:8000/clientes/lista \
  -H "Authorization: Bearer <access_token>"

# Refresh
curl -X POST http://localhost:8000/auth/refresh \
  -H "Authorization: Bearer <access_token_expirado>" \
  -H "Content-Type: application/json" \
  -d '{"refresh_token": "<refresh_token>"}'

# Logout
curl -X POST http://localhost:8000/auth/logout \
  -H "Authorization: Bearer <access_token>"

# API Key (terceros)
curl -X GET http://localhost:8000/clientes/lista \
  -H "X-Api-Key: <api_key>"
```

---

## Resumen de Archivos Modificados

- ✅ `application/libraries/IResponse.php` → Mejorada (chaining, metadata)
- ✅ `application/libraries/RateLimit.php` → Nueva
- ✅ `application/helpers/authorization_helper.php` → Expandida (refresh, blacklist)
- ✅ `application/controllers/Auth.php` → Mejorada (refresh tokens, rate limiting)
- ✅ `application/core/MY_Controller.php` → Mejorada (validación estática, rate limit)
- ✅ `database/migrations/002_create_token_blacklist_table.sql` → Nueva

---

## Conclusión

El proyecto ahora cuenta con un sistema de autenticación enterprise-grade que:
- Soporta usuarios y aplicaciones de terceros simultáneamente
- Implementa rate limiting para prevenir abuso
- Permite revocación inmediata de tokens
- Tiene refresh tokens con TTL configurable
- Registra y audita accesos
- Sigue RFC 7519 para JWT
- Es escalable y mantenible

Está listo para producción con ajustes menores de configuración.
