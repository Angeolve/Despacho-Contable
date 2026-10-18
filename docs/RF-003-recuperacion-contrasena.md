# RF-003 · Recuperación de contraseña

La recuperación utiliza el broker, la notificación y la tabla `password_reset_tokens` de Laravel. No requiere migraciones adicionales ni paquetes nuevos.

## Rutas

| Método | Ruta | Nombre |
| --- | --- | --- |
| GET | `/forgot-password` | `password.request` |
| POST | `/forgot-password` | `password.email` |
| GET | `/reset-password/{token}` | `password.reset` |
| POST | `/reset-password` | `password.update` |

Estas rutas son para visitantes sin sesión iniciada. El enlace existente de `/login` dirige a la solicitud de recuperación.

## Comportamiento y seguridad

- Se muestra la misma confirmación para correos registrados, inexistentes y solicitudes repetidas durante el periodo de espera.
- Hay una espera de 60 segundos por correo, independiente de la IP y de la existencia de la cuenta. Los POST de solicitud y restablecimiento también tienen un límite de seis peticiones por IP y minuto, con contadores separados para no consumir los límites de registro y login.
- Los tokens nativos vencen a los 60 minutos, se almacenan mediante hash y se eliminan al restablecer la contraseña. Solicitar un enlace nuevo reemplaza al anterior. Una transacción y un bloqueo de fila en MySQL serializan el canje del mismo token.
- La contraseña y su confirmación tienen exactamente las reglas del registro: 8 a 12 caracteres, una mayúscula, un número y un carácter especial.
- La nueva contraseña se almacena mediante hash. Se rota `remember_token` y se eliminan únicamente las sesiones del usuario afectado cuando se utiliza el controlador de sesiones `database`, como en el entorno local actual.
- El middleware nativo `auth.session` comprueba la huella de contraseña de las sesiones autenticadas; el login almacena esa huella desde el inicio.
- No se modifican el rol, los permisos, el correo verificado ni el consentimiento. El usuario debe iniciar sesión nuevamente después del cambio.
- Los POST tienen protección CSRF. Los tokens y las contraseñas no se conservan en los datos anteriores de formulario. Las páginas de recuperación usan `Cache-Control: no-store, private`.
- El enlace del correo utiliza el origen configurado en `APP_URL`. La notificación usa explícitamente SMTP, sin transporte `log` ni fallback a logs. Los fallos SMTP registran solamente un mensaje fijo, sin correo, token, contraseña ni detalles de la excepción.

## Configuración local

La configuración existente ya apunta a Mailpit:

```dotenv
APP_URL=http://127.0.0.1:8000
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_SCHEME=null
SESSION_DRIVER=database
```

No se modificó `.env`. Mailpit debe estar ejecutándose; su interfaz local está en `http://127.0.0.1:8025`. Si se utiliza otro host o puerto para WBalance, ajustar `APP_URL` a esa dirección y ejecutar `php artisan config:clear` antes de probar el correo.

## Verificación manual

1. Iniciar Mailpit y WBalance en la dirección de `APP_URL`; para la configuración mostrada puede utilizarse `php artisan serve`.
2. Sin sesión iniciada, abrir `/login` y pulsar **¿Olvidaste tu contraseña?**.
3. Introducir el correo de un cliente de prueba existente. Debe aparecer una confirmación genérica. Probar también un correo inexistente: la respuesta visible debe ser la misma.
4. Abrir Mailpit en el puerto 8025, localizar el correo **Restablece tu contraseña · W&A Balance** y pulsar **Restablecer contraseña**. Abrir el enlace sin sesión iniciada; si hay otra cuenta abierta, cerrar sesión o utilizar una ventana privada.
5. Probar una contraseña inválida y una confirmación diferente. Deben mostrarse errores accesibles y conservarse el foco en el primer campo inválido. Comprobar ambos botones para mostrar y ocultar las contraseñas.
6. Introducir una contraseña válida, por ejemplo `Nueva123!`, y confirmarla. Debe abrirse `/login` con una confirmación del cambio.
7. Comprobar que la contraseña anterior falla y la nueva permite el acceso. Si el correo no estaba verificado, el login debe seguir dirigiendo a la verificación.
8. Cerrar sesión y reutilizar el enlace anterior. El servidor debe rechazarlo y ofrecer solicitar otro. Para comprobar vencimiento manualmente, esperar más de 60 minutos; la prueba automatizada simula ese intervalo.
9. Si el cliente tenía otras sesiones abiertas, intentar acceder a `/cliente` desde ellas: deben volver al login. Las sesiones de otros clientes permanecen activas.
10. Revisar las dos pantallas a 320 px y en escritorio: controles utilizables, mensajes legibles y ausencia de desplazamiento horizontal.

## Pruebas

```powershell
php vendor/phpunit/phpunit/phpunit
node --test tests/JavaScript/*.test.js
npm run build
```

Las pruebas PHP usan SQLite en memoria mediante `UsesIsolatedDatabase`. Cubren envío y renderizado del correo, respuesta uniforme, límites de frecuencia, token inválido/vencido/reutilizado, contraseña y confirmación, hash actualizado, rechazo de contraseña anterior, login con contraseña nueva, conservación de rol/verificación, CSRF e invalidación de sesiones. La suite existente comprueba además la regresión de registro, verificación, login, logout y autorización.

Se comprobó también el flujo completo en Edge con una base SQLite temporal y un cliente ficticio: envío SMTP real a Mailpit, validación accesible, controles de visibilidad, cambio de contraseña, login, logout y rechazo del enlace reutilizado. Se revisaron ambas pantallas a 320 y 1440 px, sin desbordamiento horizontal. Esta comprobación no utilizó usuarios de MySQL.

## Archivos de esta implementación

Creados:

- `app/Http/Controllers/Auth/PasswordResetLinkController.php`
- `app/Http/Controllers/Auth/NewPasswordController.php`
- `app/Http/Requests/Auth/PasswordResetLinkRequest.php`
- `app/Http/Requests/Auth/ResetPasswordRequest.php`
- `resources/views/auth/forgot-password.blade.php`
- `resources/views/auth/reset-password.blade.php`
- `tests/Feature/PasswordRecoveryTest.php`
- `docs/RF-003-recuperacion-contrasena.md`

Modificados:

- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `app/Providers/AppServiceProvider.php`
- `bootstrap/app.php`
- `routes/web.php`
- `resources/views/auth/login.blade.php`
- `resources/js/login-preview.js`
- `tests/JavaScript/login-preview.test.js`

El diseño aprobado de login y registro, las migraciones, los seeders y los modelos no se modificaron. Los cambios de login se limitan al enlace de recuperación, el mensaje de confirmación y la huella de contraseña en la sesión.
