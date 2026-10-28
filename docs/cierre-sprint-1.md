# Cierre técnico del Sprint 1

Fecha de revisión: 28 de octubre de 2026.

## Corrección del HTTP 500 en autenticación

Los formularios de registro e inicio de sesión solo recuperan un valor de `old()` para los campos de texto si es una cadena. Un arreglo, incluido uno anidado, se muestra como un campo vacío y conserva su mensaje de validación. Los demás datos de contacto válidos se conservan y las contraseñas no se recuperan en los formularios.

La corrección se limita a las vistas `resources/views/auth/register.blade.php` y `resources/views/auth/login.blade.php`. No cambia las reglas de validación, controladores, modelos, roles ni contraseñas.

Se añadieron 16 casos de regresión en `RegistrationTest` y `AuthenticationTest`: arreglos simples y anidados en los seis campos de texto del registro y en los dos campos del login. Comprueban el rechazo del POST, el GET posterior con HTTP 200, la presentación del error, los valores de los campos y la ausencia de cuentas creadas o notificaciones enviadas. Utilizan SQLite en memoria.

## Pendientes conocidos trasladados al Sprint 4

Por decisión expresa del propietario, estas tres discrepancias se posponen al Sprint 4, durante el desarrollo del catálogo de servicios. Quedan aceptadas como pendientes conocidos y no bloquean el cierre de los módulos funcionales del Sprint 1.

| Pendiente | Discrepancia actual | Prueba que debe mantenerse visible |
| --- | --- | --- |
| S4-01 | El párrafo general de precios provisionales está vacío; las tarjetas conservan la indicación individual. | `LandingPageTest::test_public_landing_renders_the_complete_confirmed_catalog_without_database_queries` |
| S4-02 | Las leyendas visibles de imágenes ilustrativas están vacías. | `LandingPageTest::test_mission_vision_and_illustrative_content_are_identified_as_provisional` |
| S4-03 | El estado de catálogo vacío muestra un punto en lugar del mensaje explicativo. | `LandingPageTest::test_missing_official_information_is_not_replaced_with_fictitious_contact_data` |

No se modifican `welcome.blade.php`, los demás archivos de la landing ni `LandingPageTest`. Las tres pruebas continúan ejecutándose y reportándose como fallidas; no se eliminan, omiten ni declaran aprobadas.

Las comprobaciones manuales de navegador y entrega completa mediante Mailpit indicadas en la auditoría anterior no se consideran realizadas por las pruebas automatizadas de esta corrección.

## Resultados de esta revisión

Se verificó el arranque de Laravel en `testing` con SQLite `:memory:`, correo simulado y sesiones/caché en memoria antes de ejecutar PHPUnit. Las suites de base de datos también comprueban su conexión aislada antes de migrar. No se consultó ni modificó MySQL durante esta corrección.

| Comprobación | Resultado |
| --- | --- |
| Registro y verificación | 144 pruebas aprobadas, 1747 aserciones |
| Autenticación | 53 pruebas aprobadas, 767 aserciones |
| Roles | 18 pruebas aprobadas, 141 aserciones |
| Creación de administradores | 47 pruebas aprobadas, 694 aserciones |
| Recuperación de contraseña | 44 pruebas aprobadas, 607 aserciones |
| Total de los cinco módulos funcionales | 306 aprobadas, 0 fallidas, 3956 aserciones |
| Suite PHP completa | 313 ejecutadas: 310 aprobadas y las 3 fallidas conocidas de la landing; 4013 aserciones, 0 errores y 0 omitidas |
| JavaScript | 25 aprobadas, 0 fallidas |
| Laravel Pint | Aprobado en modo `--test` con PHP 8.3.33 de Laragon |
| Vite | Compilación aprobada, 63 módulos transformados; salida en carpeta temporal |
| `git diff --check` | Aprobado |

Las 16 regresiones nuevas se ejecutaron primero sin la corrección: 10 fallaron y reprodujeron el HTTP 500. Con la corrección, las 16 pasan en ambas ejecuciones PHP. La suite completa conserva el código de salida 1 debido exclusivamente a S4-01, S4-02 y S4-03.

Los módulos funcionales del Sprint 1 están listos para su cierre técnico, con las tres discrepancias aceptadas y trasladadas al Sprint 4. Este resultado no presenta la suite completa como aprobada ni sustituye las comprobaciones manuales pendientes.
