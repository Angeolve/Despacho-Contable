# Sprint 2 · Etapa 1: panel del cliente

## Alcance entregado

La pantalla provisional del cliente se sustituyó por un panel personal con bienvenida, menú de seis opciones, acceso informativo al catálogo público y perfil de solo lectura. La identidad utiliza borgoña `#390304`, vino `#810F1F`, verde petróleo `#002E2D`, dorado `#D5AC58` y marfil `#F8F5F0`, junto con el logotipo oficial existente.

El menú lateral de escritorio se convierte en un desplegable nativo `details`/`summary` en móvil. Funciona sin JavaScript para abrirlo y navegar; el módulo del panel añade estado accesible, cierre con Escape y devolución del foco, cierre al seleccionar un enlace, al pulsar fuera y al cambiar a escritorio. También hay un enlace para saltar al contenido y estilos de foco visible.

El encabezado muestra el nombre de la cuenta autenticada, enlace al perfil y el cierre de sesión existente mediante POST y CSRF. No se han agregado datos ficticios de contrataciones, pagos, ingresos, cálculos, resultados o notificaciones a la aplicación.

## Rutas

| Método | Ruta | Nombre | Contenido |
| --- | --- | --- | --- |
| GET/HEAD | `/cliente` | `client.home` | Inicio personalizado |
| GET/HEAD | `/cliente/perfil` | `client.profile` | Nombre, apellidos, teléfono y correo de la cuenta autenticada |
| GET/HEAD | `/cliente/mis-servicios` | `client.services` | Sección informativa «Próximamente» |
| GET/HEAD | `/cliente/calculadora` | `client.calculator` | Sección informativa; calculadora no habilitada |
| GET/HEAD | `/cliente/resultados` | `client.results` | Sección informativa «Próximamente» |

La navegación al catálogo utiliza la sección pública existente `/#servicios`; no se agregó un catálogo privado ni contratación. El cierre de sesión reutiliza `POST /logout`.

Las cinco rutas conservan los middleware `auth`, `auth.session`, `verified`, `role:cliente` y las cabeceras de caché `no-store, private`. Un administrador verificado obtiene 403, un visitante es redirigido al login y un cliente sin verificar es redirigido a verificación.

Las vistas solo utilizan `request()->user()` para obtener los datos de la cuenta. No reciben un identificador de usuario ni utilizan parámetros de consulta para elegir un perfil. Los datos se imprimen con el escape de Blade. No hay rutas de escritura para el perfil ni para las secciones futuras.

## Archivos creados

- `resources/views/components/layouts/client.blade.php`: estructura privada del cliente, encabezado, navegación y pie.
- `resources/views/components/client/navigation.blade.php`: navegación compartida entre escritorio y móvil.
- `resources/views/components/client/icon.blade.php`: iconos locales del panel.
- `resources/views/client/home.blade.php`: bienvenida, catálogo, resumen del perfil y opciones futuras.
- `resources/views/client/profile.blade.php`: datos básicos de solo lectura.
- `resources/views/client/coming-soon.blade.php`: estados informativos de las tres secciones futuras.
- `resources/css/client.css`: estilos propios del panel y sus adaptaciones responsivas.
- `resources/js/client.js`: comportamiento accesible del menú móvil.
- `tests/Feature/ClientDashboardTest.php`: 27 casos de acceso, renderizado, privacidad, escape y navegación.
- `tests/JavaScript/client-navigation.test.js`: seis pruebas del menú.
- `docs/panel-cliente-etapa-1.md`: esta documentación.

## Archivos modificados

- `routes/web.php`: se cambia únicamente la vista del cliente y se añaden sus rutas de solo lectura.
- `vite.config.js`: se añaden las dos entradas del panel; se conservan las entradas existentes.
- `tests/Feature/AuthenticationTest.php`: la aserción de contenido del cliente espera el panel nuevo; la del administrador sigue esperando su pantalla provisional. No se modifica el comportamiento de autenticación.

La compilación actualizada se generó en `public/build`, que está ignorado por Git. No se cambiaron la landing ni sus pruebas, las vistas de autenticación, el modelo User, los middleware de roles, controladores, migraciones o configuración de base de datos.

## Componentes y controles reutilizados

- `<x-brand-logo />`: logotipo oficial.
- `<x-auth.logout />`: formulario de cierre de sesión con CSRF.
- `<x-ui.icon />`: iconos existentes de usuario y flecha.
- Autenticación, verificación, autorización por rol y protección de sesión existentes.
- Catálogo público y ruta del aviso de privacidad existentes.

Los recursos del panel son entradas separadas de Vite, de modo que no se cargan en la landing ni en los formularios de autenticación.

## Funcionalidades que permanecen pendientes

- Edición del perfil y cambio de contraseña desde el panel.
- Contratación, comprobantes y confirmación de pagos.
- Consulta funcional de servicios contratados.
- Captura fiscal y cálculos de impuestos.
- Habilitación de la calculadora condicionada a contratación, pago confirmado y vigencia de 12 horas desde la confirmación.
- Resultados, documentos generados, validación profesional, reportes y notificaciones reales.

La página de calculadora explica los controles futuros, pero no tiene campos, botones de cálculo ni una ruta POST. No se comenzó la siguiente etapa del Sprint 2.

## Resultados de las pruebas

Antes de ejecutar PHPUnit se confirmó el arranque en `testing` con SQLite `:memory:`, sin configuración cacheada, sesiones/caché en memoria y correo simulado. Las suites con datos también comprueban su conexión SQLite aislada antes de migrar. Los usuarios de prueba son efímeros; no se consultó ni modificó MySQL ni se alteraron cuentas existentes.

| Comprobación | Resultado |
| --- | --- |
| Panel del cliente | 27 aprobadas, 368 aserciones |
| Panel y módulos funcionales del Sprint 1 | 333 aprobadas, 0 fallidas, 4324 aserciones |
| Suite PHP completa | 340 ejecutadas: 337 aprobadas y 3 fallidas conocidas de landing; 4381 aserciones, 0 errores y 0 omitidas |
| Suite JavaScript completa | 31 aprobadas, 0 fallidas |
| Laravel Pint | Aprobado en modo `--test`, con PHP 8.3.33 de Laragon |
| Vite | Compilación aprobada, 65 módulos transformados |
| `git diff --check` | Aprobado |

Las tres pruebas fallidas de `LandingPageTest` permanecen intactas y visibles en la suite completa, con código de salida 1:

1. `test_public_landing_renders_the_complete_confirmed_catalog_without_database_queries`: aviso general de precios provisionales ausente.
2. `test_mission_vision_and_illustrative_content_are_identified_as_provisional`: leyendas visibles de imágenes ausentes.
3. `test_missing_official_information_is_not_replaced_with_fictitious_contact_data`: mensaje del catálogo vacío sustituido por un punto.

Son los pendientes del Sprint 4 documentados en `docs/cierre-sprint-1.md`; no son regresiones nuevas ni pruebas aprobadas.

### Navegador

Se revisó en Chrome con páginas renderizadas desde las vistas reales, un cliente ficticio y SQLite en memoria. El servidor de revisión sirvió únicamente esas páginas y los recursos públicos; no utilizó MySQL ni sustituyó las pruebas de autorización del servidor. El navegador y servidor temporales se cerraron al terminar.

- Inicio a 320, 390, 768, 1024, 1100, 1440 y 1920 px, sin desbordamiento horizontal.
- Perfil, Mis servicios, Calculadora fiscal y Mis resultados a 320 y 1440 px, sin desbordamiento.
- Logotipo y recursos sin fallos HTTP; sin errores JavaScript detectados en el recorrido.
- Menú con Enter y Espacio; Escape cierra y devuelve foco; cierre al cambiar a escritorio y navegación al perfil.
- Revisión visual de inicio, perfil, calculadora y menú móvil mediante capturas.

Las pruebas PHP verifican el login real hacia el panel, logout e invalidación de sesión, restricciones de las cinco rutas, aislamiento frente a identificadores ajenos en la URL, escape de datos y rechazo de intentos de escritura.

## Revisión manual en Laragon

1. Iniciar Apache y MySQL en Laragon. La compilación ya está generada; no se necesita ejecutar Vite para revisar el panel.
2. Abrir `http://wbalance.test/login` e iniciar sesión con una cuenta cliente existente y verificada. Debe abrirse `/cliente` con su nombre.
3. Comprobar bienvenida, logotipo, menú y enlace «Explorar catálogo», que lleva al catálogo público existente.
4. Abrir «Mi perfil» y corroborar nombre, apellidos, teléfono y correo. No debe haber controles de edición o cambio de contraseña.
5. Recorrer «Mis servicios», «Calculadora fiscal» y «Mis resultados». Deben ser pantallas informativas con la indicación «Próximamente», sin datos ficticios ni acciones operativas.
6. Reducir la ventana a móvil; abrir y cerrar el menú con teclado, probar Escape y navegar al perfil. Revisar el foco visible, el desplazamiento y la consola del navegador.
7. Pulsar «Cerrar sesión». Debe volver al login; abrir otra vez cualquiera de las rutas del cliente debe redirigir al login.
8. Con una cuenta administrativa existente y verificada, confirmar que `/administrador` conserva su pantalla provisional y `/cliente` devuelve 403. No es necesario crear ni modificar cuentas.

La revisión siguió la guía local de `sites:sites`; no se registró ni publicó un sitio externo.
