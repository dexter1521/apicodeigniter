# 🚀 API RESTful con CodeIgniter - Plantilla Base

Este proyecto proporciona una **plantilla completa y robusta** para construir APIs utilizando **CodeIgniter 3.1.13** junto a **REST Controller API**. Incluye autenticación JWT, sistema de respuestas estandarizado, y un ejemplo completo de CRUD para acelerar el desarrollo.

## ✨ Características Principales

- 🔐 **Autenticación JWT** completa con validación automática
- 🎯 **Controlador base centralizado** (`MY_Controller`) con todos los métodos helper
- 📊 **Sistema de respuestas estandarizado** (`IResponse`) 
- 🛠️ **CRUD completo de ejemplo** (Controlador Clientes)
- ⚡ **Validación automática** de tokens y permisos
- 🔄 **Manejo centralizado de errores**
- 📖 **Documentación automática** disponible en `/docs`
- 🎨 **Código bien documentado** con PHPDoc completo

> **Nota:** Los controladores Auth.php y Clientes.php se entregan como referencia educativa. Adaptalos a tu dominio y reglas de negocio antes de usarlos en producción.

## 📁 Estructura del Proyecto

```
apicodeigniter/
├── application/
│   ├── controllers/
│   │   ├── Auth.php              # Autenticación JWT
│   │   └── Clientes.php          # 🎯 EJEMPLO COMPLETO DE CRUD
│   ├── core/
│   │   └── MY_Controller.php     # 🎯 CONTROLADOR BASE (todos heredan de aquí)
│   ├── libraries/
│   │   ├── IResponse.php         # 🎯 RESPUESTAS ESTANDARIZADAS
│   │   └── REST_Controller.php   # Framework REST base
│   ├── models/
│   │   ├── General_model.php     # Modelo base con operaciones CRUD
│   │   └── Clientes_model.php    # 🎯 EJEMPLO DE MODELO COMPLETO
│   ├── helpers/
│   │   └── authorization_helper.php # Validación y manejo de JWT
│   └── config/
│       ├── jwt.php               # 🎯 CONFIGURACIÓN JWT (con fallback)
│       └── database.php          # Configuración de base de datos
├── database/
│   └── migrations/
│       └── 001_create_clientes_table.sql # 🎯 SCRIPT DE BD EJEMPLO
├── GUIA_DE_USO.md               # 📖 GUÍA DETALLADA DE USO
└── README.md                    # Este archivo
```

## 🚀 Instalación Rápida

### 1. **Configurar Base de Datos**
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

### 2. **Configurar JWT**
```php
// application/config/jwt.php - Ya incluye fallback automático
$config['jwt_key'] = 'tu_clave_secreta_super_segura_aqui';
$config['token_expire_time'] = $_ENV['JWT_EXPIRE_TIME'] ?? 3600; // 1 hora por defecto
```

### 3. **Ejecutar Migraciones**
```sql
-- Ejecutar el script SQL incluido
source database/migrations/001_create_clientes_table.sql
```

### 4. **Probar la API**
```bash
# Iniciar servidor local
php -S localhost:8000

# Visitar documentación
http://localhost:8000/docs

# Probar endpoints
curl -X GET http://localhost:8000/clientes/lista \
  -H "Authorization: Bearer tu_jwt_token"
```

## 🎯 Ejemplo de Uso Completo

### **Controlador Clientes** (Ejemplo incluido):

| Método | Endpoint | Descripción | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/clientes/lista` | Lista clientes (con paginación y búsqueda) | ✅ |
| GET | `/clientes/detalle/{id}` | Obtiene cliente específico | ✅ |
| POST | `/clientes/crear` | Crea nuevo cliente | ✅ |
| PUT | `/clientes/actualizar/{id}` | Actualiza cliente | ✅ |
| DELETE | `/clientes/eliminar/{id}` | Elimina cliente (soft delete) | ✅ |

### **Crear Nuevo Controlador** (Súper Simple):

```php
<?php
class MiControlador extends MY_Controller
{
    public function lista_get()
    {
        // ✅ Autenticación automática
        if (!$this->requireAuthentication()) return;

        try {
            $datos = $this->mi_modelo->obtener_todos();
            
            // ✅ Respuesta estandarizada automática
            if (count($datos) > 0) {
                $this->sendSuccessResponse($datos, 'Datos obtenidos');
            } else {
                $this->sendErrorResponse('Sin datos', 404);
            }
        } catch (Exception $e) {
            $this->sendErrorResponse('Error: ' . $e->getMessage(), 500);
        }
    }
}
```

## 🛠️ Métodos Helper Incluidos

### **En MY_Controller** (disponibles en todos los controladores):

```php
// 🔐 Autenticación
$this->requireAuthentication()           // Valida token automáticamente
$this->getAuthenticatedUser()           // Obtiene datos del usuario
$this->validatePermissions($permisos)   // Valida permisos específicos

// 📊 Respuestas Estandarizadas  
$this->sendSuccessResponse($data, $msg, $status)     // Respuesta de éxito
$this->sendErrorResponse($msg, $status, $data)       // Respuesta de error
$this->sendValidationResponse($errores, $msg)        // Errores de validación
$this->handleUnauthorizedAccess($msg, $code)         // No autorizado
```

### **En IResponse** (métodos estáticos):

```php
IResponse::success($data, $message, $status)         // Respuesta de éxito
IResponse::error($message, $status, $data)           // Respuesta de error
IResponse::validation($errores, $message)            // Errores de validación
IResponse::unauthorized($message)                    // No autorizado (401)
IResponse::notFound($message)                        // No encontrado (404)
```

## 🎨 Formato de Respuestas Estandarizado

### ✅ **Respuesta de Éxito**:
```json
{
    "status": 200,
    "success": true,
    "response": { "datos": "aquí" },
    "messages": "Operación exitosa"
}
```

### ❌ **Respuesta de Error**:
```json
{
    "status": 400,
    "success": false,
    "response": null,
    "messages": "Mensaje de error"
}
```

## 📚 Documentación Adicional

- 📖 **[GUIA_DE_USO.md](GUIA_DE_USO.md)** - Guía completa con ejemplos detallados
- 🌐 **[CodeIgniter REST Server](https://github.com/chriskacerguis/codeigniter-restserver)** - Documentación del framework REST
- 🔍 **`/docs`** - Documentación automática de endpoints (una vez ejecutándose)

## 🎯 Ventajas como Plantilla Base

### ✅ **Para el Desarrollador**:
- ⚡ **80% menos tiempo** de configuración inicial
- 🔒 **Seguridad incorporada** desde el inicio
- 📝 **Código consistente** en todos los proyectos
- 🛠️ **Extensible** y personalizable
- 📖 **Bien documentado** y autodescriptivo

### ✅ **Para el Proyecto**:
- 🏗️ **Arquitectura sólida** y escalable
- 🔄 **Patrones probados** y consistentes
- 🧪 **Ejemplo funcional** incluido
- 📊 **Respuestas uniformes** en toda la API
- 🔐 **Autenticación robusta** lista para usar

## 🚀 ¡Empieza en Minutos!

1. **Clona** este repositorio
2. **Configura** base de datos y JWT
3. **Ejecuta** migraciones
4. **Copia** el patrón del controlador Clientes
5. **¡Desarrolla** tu API súper rápido!

---

**¡Con esta plantilla, crear APIs robustas y escalables nunca fue tan fácil!** 🎉

> 💡 **Tip**: Revisa el archivo `GUIA_DE_USO.md` para ejemplos detallados y mejores prácticas.
