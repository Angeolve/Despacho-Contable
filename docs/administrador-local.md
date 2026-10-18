# Creación autorizada de administradores locales

El comando interno está preparado y probado con SQLite en memoria. **No se ejecutó la creación contra MySQL y no se creó ninguna cuenta administrativa real. La ejecución permanece pendiente de autorización del propietario.**

## Revisión del estado actual

La consulta de MySQL fue exclusivamente de lectura y confirmó:

- Existe el rol `administrador` en `roles`.
- Existe el rol `cliente`.
- Hay **0 cuentas administrativas** y **1 cuenta cliente**.

Se revisaron `User`, `Role`, `EnsureRole` y las rutas existentes. El comando asigna el rol por su nombre mediante la relación Eloquent, sin asumir identificadores numéricos. `/administrador` y `/cliente` mantienen los middleware `auth`, `auth.session`, `verified` y el rol correspondiente.

## Comando preparado

Se puede consultar su ayuda sin crear cuentas:

```powershell
php artisan help wbalance:create-admin
```

**Solo después de recibir autorización**, desde `D:\laragon\www\wbalance`, ejecutar en una terminal interactiva de Laragon o PowerShell:

```powershell
php artisan wbalance:create-admin
```

No colocar nombre, correo, teléfono ni contraseña como argumentos. El comando solicita los datos dentro de la terminal:

1. Nombre(s).
2. Apellidos.
3. Teléfono de 10 dígitos.
4. Correo electrónico.
5. Contraseña, con entrada oculta.
6. Confirmación de contraseña, también oculta.
7. Confirmación final de creación, cuyo valor predeterminado es **no**. Responder `yes` únicamente si se autoriza esa cuenta. Pulsar Enter o responder `no` cancela sin crearla.

El comando solo funciona con `APP_ENV=local`, rechaza `--no-interaction` y no ofrece opciones para elegir el rol, transmitir contraseñas o forzar la creación. Si la terminal no puede ocultar la contraseña, aborta; no vuelve a una entrada visible.

## Validación y almacenamiento

Las reglas y los mensajes de los seis campos se reutilizan directamente de `RegisterRequest`. No se mantiene una copia independiente de las reglas.

- Nombre y apellidos obligatorios, letras Unicode y espacios, máximo 255 caracteres. Se recortan solo los espacios ordinarios externos, igual que en el registro público; se conservan los espacios internos y se rechazan tabuladores y caracteres prohibidos.
- Teléfono obligatorio con exactamente 10 dígitos ASCII y unicidad en `users`.
- Correo obligatorio, formato RFC, máximo 255 caracteres y unicidad en `users`.
- Contraseña de 8 a 12 caracteres, al menos una mayúscula, un dígito y un carácter especial. No se recorta ni cambia la contraseña capturada.
- Confirmación obligatoria e idéntica.

La inserción ocurre en una transacción y la contraseña se transforma con `Hash::make`. Las restricciones únicas de la base de datos también protegen contra duplicados concurrentes. Un correo o teléfono ya existente produce un error: **no se actualiza, promociona ni reemplaza al usuario existente**.

El rol se obtiene exclusivamente con `Role::ADMINISTRADOR` y se asigna desde el servidor. Si ese rol falta, el comando aborta sin ejecutar seeders ni crear roles.

Los errores no muestran datos capturados, contraseñas ni detalles de excepciones. El comando no escribe credenciales en logs, archivos ni argumentos. Se verificó también que un fallo posterior a la inserción revierte la transacción sin mostrar el contenido sensible de la excepción.

## Correo verificado y consentimiento

La cuenta nueva conserva `email_verified_at` vacío. El comando no marca el correo como verificado ni ofrece una opción para omitir la verificación.

No se simula una aceptación del aviso de privacidad: los campos de consentimiento quedan vacíos para esta cuenta interna. El registro público conserva su aceptación obligatoria y su almacenamiento del consentimiento.

El comando no inicia sesión ni envía correos automáticamente. Después de crearlo, el titular debe utilizar el flujo existente:

1. Abrir `/login` e ingresar las nuevas credenciales.
2. La aplicación lo dirigirá a `/email/verify`.
3. Solicitar **Reenviar enlace de verificación**.
4. Con Mailpit activo, revisar el mensaje en `http://127.0.0.1:8025` y abrir su enlace en el navegador donde se inició sesión.
5. Tras verificar el correo, la aplicación dirigirá al administrador a `/administrador`.

No compartir la contraseña en el chat ni guardarla en un comando del historial de la terminal.

## Comprobación manual de roles, después de autorizar la cuenta

Con Laragon y MySQL activos, utilizar `http://wbalance.test`:

| Sesión | Acción | Resultado esperado |
| --- | --- | --- |
| Administrador, antes de verificar | Abrir `/administrador` | Redirección a `/email/verify` |
| Administrador verificado | Abrir `/administrador` | Pantalla provisional, HTTP 200 |
| Administrador verificado | Abrir `/cliente` | HTTP 403 |
| Cliente existente verificado | Abrir `/cliente` | Pantalla provisional, HTTP 200 |
| Cliente existente verificado | Abrir `/administrador` | HTTP 403 |
| Cualquier rol | Pulsar **Cerrar sesión** | Redirección a `/login` |
| Sin sesión después del logout | Abrir cualquiera de las dos rutas privadas | Redirección a `/login` |

Cerrar la sesión administrativa antes de utilizar el cliente existente, o usar navegadores o ventanas privadas separados. Si el cliente todavía no tiene el correo verificado, primero debe completar el flujo de verificación; no obtiene acceso administrativo.

Las pruebas automatizadas existentes ya comprueban que `/register` crea exclusivamente clientes y rechaza la inyección de rol o de correo verificado. No es necesario crear otro cliente en MySQL para comprobar esa garantía.

## Archivos de esta tarea

Se crearon únicamente:

- `app/Console/Commands/CreateAdministrator.php`.
- `tests/Feature/CreateAdministratorCommandTest.php`.
- `docs/administrador-local.md`.

Laravel descubre automáticamente los comandos de `app/Console/Commands`; no fue necesario modificar rutas, bootstrap, modelos, middleware ni seeders. Tampoco se modificaron migraciones, vistas, estilos o comportamiento del login, registro o landing.

## Pruebas automatizadas

```powershell
php vendor/phpunit/phpunit/phpunit
node --test tests/JavaScript/*.test.js
```

Las pruebas nuevas usan `UsesIsolatedDatabase`, que configura una conexión SQLite en memoria antes de simular el entorno `local` requerido por el comando. Los administradores y clientes de prueba son registros efímeros de esa conexión, nunca cuentas de MySQL.

Se comprueban creación con contraseña hasheada, validaciones compartidas, duplicados sin alterar clientes, cancelación, bloqueo fuera de local, prohibición de entrada no interactiva, rol inexistente, identificadores de rol variables, captura oculta sin fallback visible, preservación exacta de la contraseña y rollback sin exposición de secretos. También se comprueba el ciclo comando → login → verificación → autorización → logout.

La comprobación manual con una nueva cuenta en MySQL queda pendiente de la autorización y de la captura de sus datos por el operador.
