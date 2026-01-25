# 📋 GUÍA DE USO - PLANTILLA API CODEIGNITER

## 🚀 Características Principales

Esta plantilla incluye:
- ✅ **Autenticación JWT** completa con validación de expiración
- ✅ **Sistema de respuestas estandarizado** usando IResponse
- ✅ **Controlador base** con métodos helper para todas las operaciones
- ✅ **CRUD completo de ejemplo** (Clientes)
- ✅ **Validación automática** de tokens
- ✅ **Manejo centralizado de errores**
- ✅ **Documentación automática** en `/docs`

> **Nota:** Los controladores Auth.php y Clientes.php funcionan como blueprint ilustrativo. Personalízalos con tus modelos, reglas de acceso y validaciones antes de integrarlos en entornos reales.

## 📁 Estructura del Proyecto

```
apicodeigniter/
├── application/
│   ├── controllers/
│   │   ├── Auth.php              # Autenticación JWT
│   │   └── Clientes.php          # 🎯 EJEMPLO COMPLETO DE CRUD
│   ├── core/
│   │   └── MY_Controller.php     # 🎯 CONTROLADOR BASE (hereda todos)
│   ├── libraries/
│   │   ├── IResponse.php         # 🎯 RESPUESTAS ESTANDARIZADAS
│   │   └── REST_Controller.php   # Framework REST
│   ├── models/
│   │   ├── General_model.php     # Modelo base
│   │   └── Clientes_model.php    # 🎯 EJEMPLO DE MODELO COMPLETO
│   ├── helpers/
│   │   └── authorization_helper.php # Validación JWT
│   └── config/
│       ├── jwt.php               # 🎯 CONFIGURACIÓN JWT
│       └── database.php          # Configuración BD
├── database/
│   └── migrations/
│       └── 001_create_clientes_table.sql # Script de BD
└── README.md
```

## ⚡ Configuración Rápida

### 1. Configurar Base de Datos
```php
// application/config/database.php
$db['default'] = array(
    'hostname' => 'localhost',
    'username' => 'tu_usuario',
    'password' => 'tu_password',
    'database' => 'tu_base_datos',
    // ... resto de configuración
);
```

### 2. Configurar JWT
```php
// application/config/jwt.php
$config['jwt_key'] = 'tu_clave_secreta_aqui';
$config['token_expire_time'] = 3600; // 1 hora
```

### 3. Ejecutar Migraciones
```sql
-- Ejecutar en tu base de datos
source database/migrations/001_create_clientes_table.sql
```

## 🎯 Ejemplo de Uso - Controlador Clientes

El controlador `Clientes` es un **ejemplo completo** de cómo implementar un CRUD usando esta plantilla:

### Endpoints Disponibles:

| Método | Endpoint | Descripción | Autenticación |
|--------|----------|-------------|---------------|
| GET | `/clientes/lista` | Lista todos los clientes | ✅ Requerida |
| GET | `/clientes/detalle/{id}` | Obtiene un cliente específico | ✅ Requerida |
| POST | `/clientes/crear` | Crea un nuevo cliente | ✅ Requerida |
| PUT | `/clientes/actualizar/{id}` | Actualiza un cliente | ✅ Requerida |
| DELETE | `/clientes/eliminar/{id}` | Elimina un cliente (soft delete) | ✅ Requerida |

### Ejemplos de Peticiones:

#### 🔐 Obtener Token JWT
```bash
POST /auth/login
Content-Type: application/json

{
    "usuario": "admin",
    "password": "password"
}
```

#### 📋 Listar Clientes
```bash
GET /clientes/lista
Authorization: Bearer {tu_jwt_token}

# Con parámetros opcionales:
GET /clientes/lista?limit=10&offset=0&buscar=juan
```

#### ➕ Crear Cliente
```bash
POST /clientes/crear
Authorization: Bearer {tu_jwt_token}
Content-Type: application/json

{
    "nombre": "Juan Pérez",
    "email": "juan@example.com",
    "telefono": "+1234567890",
    "direccion": "Av. Principal 123"
}
```

#### ✏️ Actualizar Cliente
```bash
PUT /clientes/actualizar/1
Authorization: Bearer {tu_jwt_token}
Content-Type: application/json

{
    "nombre": "Juan Pérez Actualizado",
    "telefono": "+0987654321"
}
```

#### 🗑️ Eliminar Cliente
```bash
DELETE /clientes/eliminar/1
Authorization: Bearer {tu_jwt_token}
```

## 🛠️ Crear Nuevos Controladores

### Plantilla Base para Nuevo Controlador:

```php
<?php defined('BASEPATH') or exit('No direct script access allowed');

class MiControlador extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Mi_model', 'mi_modelo');
    }

    // ✅ Método GET simple con autenticación automática
    public function lista_get()
    {
        if (!$this->requireAuthentication()) {
            return; // MY_Controller maneja automáticamente el error
        }

        try {
            $datos = $this->mi_modelo->obtener_todos();
            
            if (count($datos) > 0) {
                $this->sendSuccessResponse($datos, 'Datos obtenidos correctamente');
            } else {
                $this->sendErrorResponse('No se encontraron datos', 404);
            }
        } catch (Exception $e) {
            $this->sendErrorResponse('Error: ' . $e->getMessage(), 500);
        }
    }

    // ✅ Método POST con validación
    public function crear_post()
    {
        if (!$this->requireAuthentication()) {
            return;
        }

        try {
            $datos = json_decode($this->input->raw_input_stream, true);
            
            if (!$datos) {
                $this->sendErrorResponse('Datos JSON inválidos', 400);
                return;
            }

            $resultado = $this->mi_modelo->crear($datos);
            
            if ($resultado['exito']) {
                $this->sendSuccessResponse($resultado['data'], $resultado['mensaje'], 201);
            } else {
                $this->sendErrorResponse($resultado['mensaje'], 400, $resultado['errores'] ?? null);
            }
        } catch (Exception $e) {
            $this->sendErrorResponse('Error: ' . $e->getMessage(), 500);
        }
    }
}
```

## 🎨 Métodos Helper Disponibles en MY_Controller

### Autenticación:
```php
// Valida autenticación y continúa o responde error automáticamente
if (!$this->requireAuthentication()) return;

// Obtiene datos del usuario autenticado
$usuario = $this->getAuthenticatedUser();

// Valida permisos específicos
if (!$this->validatePermissions(['admin', 'user'])) return;
```

### Respuestas Estandarizadas:
```php
// Respuesta de éxito
$this->sendSuccessResponse($datos, 'Mensaje', 200);

// Respuesta de error
$this->sendErrorResponse('Mensaje de error', 400, $datos_adicionales);

// Respuesta de validación
$this->sendValidationResponse($errores, 'Datos inválidos', 422);

// Respuesta de no autorizado
$this->handleUnauthorizedAccess('Token inválido', 401);
```

## 🔧 Personalización

### Agregar Nuevos Métodos Helper:
```php
// En MY_Controller.php
protected function validarRol($rol_requerido)
{
    $usuario = $this->getAuthenticatedUser();
    return $usuario && isset($usuario->rol) && $usuario->rol === $rol_requerido;
}

protected function logActivity($accion, $detalles = null)
{
    // Tu lógica de logging aquí
}
```

### Modificar Respuestas por Defecto:
```php
// En IResponse.php - agregar nuevos métodos estáticos
public static function customResponse($data, $message, $code)
{
    $response = new self();
    $response->setStatus($code);
    $response->setSuccess($code < 400);
    $response->setResponse($data);
    $response->setMessage($message);
    return $response;
}
```

## 📊 Estructura de Respuestas

Todas las respuestas siguen el mismo formato:

### ✅ Respuesta de Éxito:
```json
{
    "status": 200,
    "success": true,
    "response": { "datos": "aquí" },
    "messages": "Mensaje de éxito"
}
```

### ❌ Respuesta de Error:
```json
{
    "status": 400,
    "success": false,
    "response": null,
    "messages": "Mensaje de error"
}
```

### 🔍 Respuesta de Validación:
```json
{
    "status": 422,
    "success": false,
    "response": null,
    "messages": {
        "nombre": "El nombre es requerido",
        "email": "El email no es válido"
    }
}
```

## 🚀 Ventajas de Esta Plantilla

1. **🔥 Desarrollo Rápido**: 80% del trabajo base ya está hecho
2. **🔒 Seguridad Incorporada**: JWT y validación automática
3. **📝 Consistencia**: Todas las respuestas siguen el mismo formato
4. **🛠️ Extensible**: Fácil agregar nuevas funcionalidades
5. **📖 Bien Documentado**: Código autodocumentado con PHPDoc
6. **🔄 Reutilizable**: Base sólida para múltiples proyectos
7. **🧪 Probado**: Ejemplo completo funcional incluido

## 📝 Notas Importantes

- **Siempre hereda de `MY_Controller`** para obtener todas las funcionalidades
- **Usa `requireAuthentication()`** al inicio de métodos protegidos
- **Usa los métodos `send*Response()`** para respuestas consistentes
- **Maneja excepciones** adecuadamente con try-catch
- **Valida datos de entrada** antes de procesarlos
- **Documenta tus métodos** siguiendo el patrón PHPDoc

¡Con esta plantilla puedes crear APIs robustas y escalables en tiempo récord! 🎉
