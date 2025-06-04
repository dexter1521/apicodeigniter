# API RESTful con CodeIgniter

Este proyecto proporciona una plantilla en español para construir APIs utilizando **CodeIgniter 3.1.13** junto a **REST Controller API** y **APIDocs**. Incluye una configuración mínima para comenzar rápidamente y ejemplos de autenticación con tokens JWT.

## Documentación

- [CodeIgniter REST Server](https://github.com/chriskacerguis/codeigniter-restserver)
- [CodeIgniter APIDocs](https://github.com/owen1025/codeigniter-apidocs)

La documentación generada automáticamente está disponible visitando la ruta `/docs` una vez que la aplicación se encuentre en ejecución.

## Requisitos

- PHP 7.2 o superior
- CodeIgniter 3.1.13

## Librerías precargadas

- **Database**
- **Email**
- **Form validation**
- **IResponse** (para respuestas consistentes de la API)

### Helpers incluidos

- **url**
- **file**

## Características principales

- Autenticación mediante JWT configurable en `application/config/jwt.php`.
- Controlador base `MY_Controller` con validación de tokens y manejo centralizado de errores.
- Modelo `General_model` que proporciona operaciones CRUD genéricas.
- Vista de documentación automática de endpoints.

## Cómo empezar

1. Clona este repositorio.
2. Configura tu base de datos en `application/config/database.php`.
3. Actualiza la llave y opciones de expiración en `application/config/jwt.php`.
4. (Opcional) Ejecuta `composer install` si deseas utilizar las dependencias para pruebas.
5. Inicia el servidor local con `php -S localhost:8000` o configura tu servidor web preferido.
6. Visita `http://localhost:8000/docs` para consultar la documentación generada.

Con estas bases podrás crear rápidamente nuevas rutas, modelos y controladores para tu API.
