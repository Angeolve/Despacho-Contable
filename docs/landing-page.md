# Landing pública de W&A Balance

La landing se implementó en la ruta pública `/` del proyecto Laravel existente, tomando como referencia la composición visual proporcionada. El trabajo es local en Laragon; no se creó ni publicó un sitio alojado externo.

## Secciones

- Encabezado borgoña con el logotipo oficial, navegación por anclas y acceso a `/login`.
- Menú móvil nativo mediante `details` y `summary`, con estado accesible, cierre mediante Escape, devolución de foco, cierre al elegir un enlace y al cambiar a escritorio.
- Hero con el texto solicitado, botones hacia servicios/contacto e imagen ilustrativa.
- Franja verde petróleo con los cuatro beneficios proporcionados.
- Catálogo completo de ocho servicios, sin botón que oculte parte del listado.
- Sección «Comprometidos con tu tranquilidad» con los cuatro beneficios solicitados.
- ¿Quiénes somos?, misión y visión, con los textos institucionales proporcionados.
- Tres consejos generales identificados como contenido provisional, sin fechas, porcentajes ni obligaciones fiscales específicas.
- Contacto con WhatsApp, correo, Instagram y el aviso de privacidad existente en PDF.
- Footer con el logotipo oficial, navegación, acceso y aviso de privacidad.

## Servicios y precios

Los datos están en `config/landing.php`; no se consulta MySQL para construir el catálogo. Se incluyeron los ocho servicios confirmados durante esta tarea:

1. Asesoría fiscal y contable.
2. Cálculo y presentación de impuestos.
3. Trámites ante el SAT y AAFY.
4. Facturación electrónica.
5. Cálculo de nómina.
6. Contabilidad general.
7. Retroalimentación bimestral con análisis financiero.
8. Calculadora preliminar de impuestos para docentes de secundaria.

Por indicación expresa, cada servicio muestra **$500** como **precio provisional**. No se agregó moneda, periodicidad de cobro, impuestos incluidos, plazos de entrega ni condiciones no confirmadas. Las descripciones son resúmenes directos de los servicios proporcionados.

`price_confirmed` controla si un precio está autorizado para mostrarse; `price_provisional` conserva la indicación visible de que aún es provisional. Un precio no autorizado no se renderiza. «Más información» es únicamente el elemento visual solicitado: no crea una ruta ni activa contratación.

## Contactos confirmados

- WhatsApp: **999 239 3507**, enlace `https://wa.me/529992393507`.
- Correo: **taxbalance2025@gmail.com**.
- Instagram: **@taxbalance.wa**, enlace `https://www.instagram.com/taxbalance.wa/`.

No se publicaron dirección física, horarios ni otros datos no proporcionados. Los enlaces externos que abren una pestaña incluyen `rel="noopener noreferrer"`.

## Información pendiente

- Aprobación del propietario de los textos institucionales, misión y visión.
- Confirmación de precios definitivos y, si corresponde, moneda y condiciones comerciales.
- Material aprobado para reemplazar los consejos generales provisionales.
- Fotografías autorizadas del despacho, si se desea sustituir las ilustraciones.
- Dirección física y horarios, únicamente si se desea publicarlos y están confirmados.

## Archivos de esta tarea

Modificados:

- `resources/views/welcome.blade.php`: reemplaza la bienvenida inicial de Laravel.
- `resources/css/app.css`: importa la hoja de estilos de la landing.
- `resources/js/app.js`: importa el comportamiento de navegación pública.

Creados:

- `config/landing.php`: catálogo y contactos estáticos.
- `resources/css/landing.css`: estilos adaptables y aislados mediante clases de la landing.
- `resources/js/landing.js`: menú móvil y sección activa de navegación.
- `resources/views/components/landing/icon.blade.php`: iconos vectoriales locales.
- `resources/views/components/landing/ornament.blade.php`: detalles decorativos dorados y verde petróleo.
- `resources/views/components/landing/service-card.blade.php`: tarjeta reutilizable de servicio.
- `public/images/landing/consultoria-hero-1600.webp`.
- `public/images/landing/consultoria-hero-800.webp`.
- `public/images/landing/arquitectura-960.webp`.
- `public/images/landing/arquitectura-480.webp`.
- `tests/Feature/LandingPageTest.php`.
- `docs/landing-page.md`.

El logotipo, las vistas y funciones de autenticación, las rutas existentes, modelos, migraciones y seeders no se modificaron durante esta tarea. Se conservaron los cambios de las tareas anteriores que ya estaban en el directorio de trabajo.

## Recursos visuales y procedencia

Se utilizó la herramienta integrada **image_gen** en modo de generación, sin CLI ni API adicional. Las imágenes son ilustrativas y provisionales, no fotografías del despacho. No se descargaron imágenes de sitios externos. El logotipo es el SVG oficial existente y no se regeneró.

Los resultados seleccionados se convirtieron a WebP con GD, sin nuevas dependencias y sin cambiar la composición, y se guardaron dentro del proyecto. Las cuatro variantes pesan en conjunto **223122 bytes**, aproximadamente 218 KiB. Se utilizan `srcset`, tamaños explícitos y carga diferida para la imagen secundaria; la imagen principal tiene prioridad de carga.

Ubicación de los archivos finales: `D:/laragon/www/wbalance/public/images/landing/`.

### Prompt final del hero

```text
Use case: photorealistic-natural. Asset type: wide horizontal website hero background for an elegant accounting, tax and legal consultancy landing page. Generate a photorealistic editorial still life, wide cinematic landscape, intended display 1600 by 750 pixels. Scene: a refined professional desk in a warmly lit library office at dusk, dark walnut desktop, a bronze-gold Lady Justice statue with scales on the right of center, several stacked burgundy and dark teal law books near center, pen and cream paper documents foreground, a modern graphite laptop with a completely blank back cover occupying the far right, soft blurred wooden shelves and warm window light behind. Composition: all important objects in the RIGHT 65 percent of the frame, LEFT 40 percent very dark burgundy shadow and soft negative space, naturally blending into near-black burgundy (#390304) for white webpage typography to be overlaid by HTML. Camera at desk height, premium subtle professional photography, realistic intricate bronze, warm gold highlights, deep burgundy and petroleum green accents, luxurious but credible. No people. No text, no readable lettering on books or paper, NO logos, no watermarks, no UI or webpage mockup, no borders, no decorative overlays. This is an illustrative provisional asset, not a photo of a real firm. Output one high quality wide photograph.
```

### Prompt final de arquitectura

```text
Use case: photorealistic-natural. Asset type: supporting editorial image for a burgundy and gold professional legal/accounting consultancy website, clearly an illustrative provisional image not claiming to depict any actual office. Primary request: a refined contemporary office building facade photographed from a low angle, pale warm limestone and dark bronze window frames, elegant geometric vertical lines and reflective glass, warm evening sky and golden sunlight, subtle city context, no signs, no addresses, no people, no brands, no lettering, no logo, no watermark. Editorial architectural photograph, natural credible details, warm cream, muted gold, deep shadows and subtle petroleum-green reflections. Landscape composition about 4:3; building diagonals give a confident elegant composition similar to luxury professional-services landing-page photography. Focus on beautiful architecture rather than an identifiable landmark. Output one photograph only, not a webpage, no borders or overlays.
```

## Validación

Comandos ejecutados:

```powershell
php vendor/phpunit/phpunit/phpunit
node --test tests/JavaScript/*.test.js
npm run build
git diff --check
```

Resultados:

- PHP: **250 pruebas correctas y 2974 aserciones**. Incluye cinco nuevas pruebas de landing y la regresión de registro, verificación, login, logout, recuperación y roles.
- Las pruebas de autenticación usan SQLite aislado. La prueba del catálogo comprueba además que la landing renderiza sin solicitar conexiones de base de datos.
- JavaScript: **25 pruebas correctas**.
- Compilación Vite y revisión de diferencias: correctas.
- Navegador Edge: comprobación de la landing a **320, 390, 768, 1024, 1440 y 1536 px**, sin desbordamiento horizontal, con ocho servicios y cuadrículas de una, dos y cuatro columnas.
- Comprobación real de menú con Enter y Escape, foco visible, retorno del foco, cierre al seleccionar una sección y al pasar a escritorio, navegación por anclas, carga de imágenes y apertura de login, registro y recuperación, sin errores JavaScript.

La comprobación de navegador utilizó un servidor PHP temporal con SQLite en memoria y sesiones/caché en memoria; no utilizó usuarios de MySQL. El servidor temporal se detuvo al terminar.

## Visualizar desde Laragon

1. Iniciar Apache y MySQL en Laragon para utilizar el entorno habitual de la aplicación.
2. Abrir **http://wbalance.test/**, que es la dirección actual de `APP_URL`.
3. La compilación está generada; no es necesario ejecutar Vite para verla. Para editar con recarga durante el desarrollo, utilizar `npm run dev` y mantener el proceso activo.
4. Si el dominio aún no está configurado en Apache, utilizar la opción de sitios automáticos de Laragon y recargar su configuración. Como alternativa, ejecutar `php artisan serve` y abrir la dirección que indique.
5. Comprobar los ocho servicios, los precios provisionales, los enlaces de contacto y el PDF. Probar el menú en una ventana móvil y el botón de inicio de sesión.

No se implementaron contratación, pagos, calculadora funcional, paneles ni otras funciones fuera de la navegación pública solicitada.
