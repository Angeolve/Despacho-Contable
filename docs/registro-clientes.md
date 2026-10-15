# Registro de clientes y verificación

El registro usa las vistas existentes, validación de Laravel y el rol `cliente`
elegido por el servidor. No hay un formulario de acceso funcional ni paneles.

## Preparación de MySQL

La migración siguiente está preparada y **no se ejecutó sobre MySQL**. Una vez
autorizada, aplicarla desde la raíz del proyecto, usando PHP 8.3 de Laragon:

```powershell
php artisan migrate --database=mysql --path=database/migrations/2026_10_15_075825_add_privacy_acceptance_to_users_table.php
```

Se agregan `privacy_policy_accepted_at` y `privacy_policy_version`, ambos anulables
para conservar cuentas previas sin inventar su consentimiento. El registro público
siempre exige aceptación y asigna ambos valores desde el servidor. La versión es
el SHA-256 del PDF oficial, no una fecha ni un identificador enviado por el cliente.
No se recopilan direcciones IP ni agentes de navegador para el consentimiento.

Los roles deben existir. Si todavía no están cargados, el seeder idempotente
`php artisan db:seed --class=RoleSeeder` crea únicamente los dos roles, sin usuarios.

## Mailpit local

La configuración local y `.env.example` usan:

```dotenv
APP_URL=http://127.0.0.1:8000
MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="no-reply@wbalance.test"
MAIL_FROM_NAME="W&A Balance"
```

Iniciar Mailpit desde Laragon. Si no aparece en su menú, el ejecutable instalado
puede iniciarse en otra terminal:

```powershell
& 'D:\laragon\bin\mailpit\1.22.3\mailpit.exe' --listen 127.0.0.1:8025 --smtp 127.0.0.1:1025
```

La bandeja local está en `http://127.0.0.1:8025`. Estos datos son exclusivamente de
desarrollo; Mailpit captura mensajes y no los entrega a destinatarios externos.
Si se utiliza un dominio de Laragon, ajustar `APP_URL` y conservar ese mismo host
durante todo el flujo. Tras cambiar configuración en caché, ejecutar
`php artisan config:clear`. La notificación nativa se envía de forma síncrona;
no requiere un trabajador de colas.

## Prueba manual

Después de aplicar la migración autorizada y arrancar Mailpit:

1. Ejecutar `php artisan serve` y abrir `http://127.0.0.1:8000/register`.
2. Abrir el aviso desde su enlace: debe abrir el PDF oficial en otra pestaña.
   También está disponible en `/documents/aviso-de-privacidad.pdf`.
3. Probar errores de formato, contacto duplicado y ausencia de aceptación.
   Los errores se muestran bajo el campo; las contraseñas no se recuperan.
4. Usar un correo y teléfono nuevos, una contraseña como `Ejemplo1!`, confirmarla
   y aceptar el aviso. Esto crea una cuenta **cliente real** en la base configurada.
5. En Mailpit, abrir el correo de verificación y su enlace en el **mismo navegador**
   usado para registrar la cuenta, conservando la sesión y el host.
6. Se mostrará `/registro/completado`. Antes de verificar, esa ruta redirige a
   `/email/verify`; la sesión no concede acceso a funciones verificadas.

Los enlaces firmados caducan a los 60 minutos por defecto. La pantalla de espera
permite reenviarlos, con un límite de seis solicitudes por minuto. Sin la sesión
de registro, Laravel redirige a `/login`, cuyo desarrollo funcional sigue pendiente.
Si SMTP falla, la cuenta conserva su estado sin verificar y permite reintentar el
envío desde la pantalla de espera, sin crear una segunda cuenta.

## PDF y versiones

El archivo público es una copia binaria del documento original en `requerimientos`.
`config/privacy.php` fija su ruta y SHA-256. El registro se detiene si el contenido
público no coincide con esa versión aprobada. Una futura actualización debe conservar
el PDF anterior como evidencia de su versión, sustituir el archivo solo con un aviso
aprobado y actualizar la configuración; nunca sobrescribir el consentimiento previo.

## Pruebas

```powershell
php vendor/bin/phpunit --no-progress
node --test tests/JavaScript/register-validation.test.js
npm run build
```

Las pruebas PHP configuran explícitamente SQLite en memoria, ejecutan `migrate`
solo en esa conexión y simulan las notificaciones. No usan `migrate:fresh`, no
tocan MySQL y no crean cuentas administrativas. Incluyen CSRF, PDF, roles,
consentimiento, contraseñas, verificación firmada y límites de reenvío.

Para futuras funciones privadas, conservar el grupo `auth` y `verified`; todavía
no se implementó ninguna de ellas.
