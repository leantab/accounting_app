# Directrices de Diseño: Una Identidad Visual para la Gestión Empresarial

Como diseñadores, nuestro objetivo no es simplemente construir interfaces funcionales, sino crear entornos de autoridad y calma. Para el empresario latinoamericano, el software no debe sentirse como una herramienta técnica compleja, sino como un aliado estratégico. Este sistema de diseño rechaza lo genérico; se aleja de los bordes rígidos y las sombras pesadas de los años 2010 para abrazar una estética "Editorial Oscura" de alta gama.

## 1. El Norte Creativo: "El Tablero Soberano"

Nuestra estrella polar es el concepto de **El Tablero Soberano**. Buscamos una experiencia que emule la oficina de un alto ejecutivo: iluminación tenue, materiales nobles y un enfoque absoluto en la información crítica. 

Rompemos el molde de las cuadrículas rígidas de SaaS mediante el uso de **asimetría intencional** y **jerarquía tonal**. En lugar de separar secciones con líneas, utilizamos la profundidad y el espacio negativo. El diseño debe sentirse expansivo, profesional y, sobre todo, premium.

---

## 2. Paleta Cromática y Profundidad Tonal

La base de este sistema es el color `surface` (`#060e20`), un azul profundo casi abisal que proporciona un contraste superior para la legibilidad sin la fatiga visual del negro puro.

### El "No-Line" Rule (La Regla de Cero Líneas)
Queda estrictamente prohibido el uso de bordes sólidos de 1px para delimitar secciones. La estructura se define mediante el cambio de tono de fondo. 
*   **Sección Principal:** `surface` (`#060e20`)
*   **Contenedor de Contenido:** `surface-container-low` (`#091328`)
*   **Tarjetas/Cards:** `surface-container` (`#0f1930`)

### Jerarquía de Superficies y Capas
Visualice la interfaz como hojas de cristal ahumado superpuestas. 
*   **Nivel 0 (Fondo):** `surface`
*   **Nivel 1 (Navegación/Layout):** `surface-container-low`
*   **Nivel 2 (Componentes Interactivos):** `surface-container-high` (`#141f38`)
*   **Nivel 3 (Modales/Popovers):** `surface-container-highest` (`#192540`)

### Acentos de Firma: Glass & Gradient
Para evitar una apariencia plana, los botones principales y estados activos deben utilizar un degradado sutil de `primary` (`#ffad3a`) a `primary-container` (`#f59e0a`). Esto aporta una "vibración" orgánica que el color sólido no posee.

---

## 3. Tipografía Editorial

Utilizamos un sistema de doble familia tipográfica para elevar el tono de voz de la plataforma.

*   **Display & Headlines (Manrope):** Elegida por su estructura geométrica moderna. Se utiliza en tamaños grandes para comunicar hitos y datos clave. Transmite confianza y modernidad.
*   **Body & UI (Inter):** La referencia en legibilidad. Se utiliza para toda la gestión de datos, formularios y lectura extendida.

**Jerarquía Sugerida:**
*   **Headline-LG (2rem):** Para encabezados de sección.
*   **Title-MD (1.125rem):** Para títulos de tarjetas, en `on-surface`.
*   **Body-MD (0.875rem):** El estándar para gestión de datos, utilizando `on-surface-variant` (`#a3aac4`) para reducir la carga cognitiva.

---

## 4. Elevación y Atmósfera

En este sistema, la profundidad no se "dibuja", se "siente".

*   **El Principio de Capas:** Para elevar un elemento, muévalo un paso arriba en la escala de `surface-container`. Por ejemplo, una tarjeta de detalle sobre un fondo `surface-container-low` debe ser `surface-container-highest`.
*   **Sombras Ambientales:** Si un elemento debe flotar (como un menú contextual), use una sombra extra-difusa. El color de la sombra debe ser un tinte de `background` con opacidad del 40%, nunca gris neutro.
*   **Bordes Fantasma (Ghost Borders):** Si la accesibilidad requiere un límite visual, use `outline-variant` (`#40485d`) con una opacidad del 15%. Debe ser casi imperceptible, sirviendo solo como una guía óptica.
*   **Glassmorphism:** Los elementos flotantes deben usar `backdrop-blur` de al menos 12px combinado con un fondo `surface` al 70% de opacidad.

---

## 5. Componentes de Firma

### Botones (Acciones de Negocio)
*   **Primario (Ámbar):** Degradado de `primary` a `primary-container`. Radio de `md` (0.375rem). Texto en `on-primary-fixed` (`#2a1700`) para máximo contraste.
*   **Secundario (Azul):** Fondo `secondary-container` (`#005ac2`) con texto en `on-secondary-container` (`#f7f7ff`).

### Tarjetas (Cards)
Prohibido el uso de divisores internos. Para separar el encabezado del cuerpo en una tarjeta, utilice un salto de espacio `spacing-4` (0.9rem). El borde de la tarjeta debe ser inexistente, confiando en el cambio tonal entre `surface-container` y el fondo.

### Inputs de Datos
*   **Estado de Reposo:** Fondo `surface-container-highest`, sin borde.
*   **Estado de Foco:** Borde sutil de 1px en `primary` con un resplandor (glow) suave del mismo color al 10% de opacidad.

### Chips de Estado (Indicadores de Éxito/Error)
No usar cajas sólidas. Usar el color de fondo `error_container` o `secondary_container` con opacidad baja (20%) y texto en el color pleno (`error` o `secondary`). Esto mantiene la interfaz limpia y profesional.

---

## 6. Do’s and Don’ts (Mandamientos del Sistema)

### ✅ Qué hacer (Do’s)
*   **Use el espacio como estructura:** Si dos elementos están relacionados, use `spacing-2`. Si no lo están, use `spacing-8`.
*   **Priorice el contraste:** El texto importante siempre debe usar `on-surface` (`#dee5ff`).
*   **Localización LATAM:** Use terminología financiera clara (ej. "Facturación", "Cuentas por Cobrar") en lugar de traducciones literales de "Billing" o "Accounts Receivable".

### ❌ Qué no hacer (Don'ts)
*   **No use divisores horizontales:** Los `hr` o líneas de división rompen la fluidez editorial. Use espacios en blanco o cambios de color de fondo.
*   **No use sombras pesadas:** Si la sombra se nota a primera vista, es demasiado oscura. Debe ser un susurro, no un grito.
*   **No use bordes de 100% opacidad:** Destruyen la estética premium y hacen que el software parezca una hoja de cálculo antigua.

---

**Nota del Director:** Este sistema no es una limitación, es una invitación a la precisión. Cada píxel debe tener una razón de ser. Si un elemento no comunica información o no guía la acción, no pertenece aquí. Estamos construyendo el futuro de la gestión empresarial en América Latina. Hagámoslo con elegancia.