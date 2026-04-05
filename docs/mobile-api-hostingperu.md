# API movil y despliegue en HostingPeru

Este proyecto ya incluye la API base para la app movil Flutter.

## Archivos agregados o modificados

- `bootstrap/app.php`
- `routes/api.php`
- `app/Http/Controllers/Api/MobileController.php`
- `app/Models/AppUser.php`

## Endpoints disponibles

- `POST /api/mobile/login`
- `POST /api/mobile/logout`
- `GET /api/mobile/bootstrap`
- `POST /api/mobile/attendance`

## URL base para Flutter

En produccion la app movil debe usar:

```dart
const String kApiBaseUrl = 'https://presenteya.com/api';
```

No usar `:8000` en produccion.

## Requisitos previos en HostingPeru

- PHP 8.2 o superior
- Composer habilitado
- Extensiones PHP necesarias para Laravel y Google API
- Acceso al Administrador de archivos, Git o SSH
- Base de datos de produccion configurada en `.env`

## Pasos de despliegue

### 1. Subir el codigo

Sube estos cambios al hosting por Git, SSH, FTP o Administrador de archivos.

Si usas Git en el servidor:

```bash
cd /ruta/de/tu/proyecto
git pull
```

Si no usas Git, reemplaza los archivos modificados manualmente.

### 2. Verificar `.env`

Confirma que en produccion existan y sean correctos:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://presenteya.com

DB_CONNECTION=mysql
DB_HOST=...
DB_PORT=3306
DB_DATABASE=mar26pres0826d_control_asistencia
DB_USERNAME=...
DB_PASSWORD=...
```

Si usas Google Sheets, confirma tambien:

- `storage/app/google-credentials.json`
- permisos de lectura para ese archivo

### 3. Instalar dependencias

En el servidor:

```bash
composer install --no-dev --optimize-autoloader
```

Si el frontend web tambien se recompila en el servidor:

```bash
npm install
npm run build
```

Si ya subes `public/build` compilado desde local, no hace falta compilar Node en produccion.

### 4. Ejecutar migraciones

Muy importante: la API movil usa Sanctum y requiere la tabla `personal_access_tokens`.

Ejecuta:

```bash
php artisan migrate --force
```

### 5. Limpiar cache y regenerar caches

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 6. Verificar permisos

Confirma permisos de escritura para:

- `storage/`
- `bootstrap/cache/`

En Linux normalmente:

```bash
chmod -R 775 storage bootstrap/cache
```

### 7. Probar la API movil

Prueba en navegador o Postman:

- `https://presenteya.com/api/mobile/login`

Como es `POST`, lo ideal es probar con Postman o Thunder Client enviando:

```json
{
  "email": "tu_correo",
  "password": "tu_password",
  "role": "director",
  "device_name": "android"
}
```

Debe responder con:

- `token`
- `user`
- `school`

Luego prueba:

- `GET /api/mobile/bootstrap` con `Authorization: Bearer TOKEN`
- `POST /api/mobile/attendance` con `Authorization: Bearer TOKEN`

## Si HostingPeru no da acceso SSH

Haz esto desde el panel:

1. Sube los archivos modificados.
2. Verifica `.env`.
3. Ejecuta las migraciones desde el terminal del panel, si existe.
4. Si no existe terminal, pide a soporte de HostingPeru que ejecuten:

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Checklist final

- `https://presenteya.com` sigue funcionando
- existe `https://presenteya.com/api/mobile/login`
- `php artisan migrate --force` se ejecuto correctamente
- la tabla `personal_access_tokens` existe
- Google credentials existen en `storage/app/google-credentials.json`
- Flutter usa `https://presenteya.com/api`
