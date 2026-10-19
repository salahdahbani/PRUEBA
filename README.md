# API de reservas de alojamiento

API en PHP puro (sin framework) para gestionar reservas: listar con filtros, ver el detalle, crear una reserva y confirmarla o cancelarla. Cada cambio queda guardado en un historial.

Además hay una pequeña web (con Twig) para usar la API desde el navegador. Es un extra: la API funciona sin ella.

## Cómo levantarlo en local

Necesitas PHP 8, MySQL o MariaDB, Apache (MAMP, WAMP o Docker) y Composer (solo para la web).

1. **Copia el proyecto** en la carpeta pública de Apache (`htdocs`, `www` o `/var/www/html`).

2. **Crea la base de datos** importando el fichero `database.sql`. Crea las tablas `reserva` y `reserva_evento` y mete reservas de ejemplo en distintos estados:

   ```bash
   mysql -u root -p < database.sql
   ```

   También se puede importar desde phpMyAdmin (pestaña Importar).

3. **Configura la conexión.** Copia `config.example.php` como `config.php` y pon tus datos:

   ```php
   define('DB_HOST', 'localhost');
   define('DB_PORT', '3306');
   define('DB_NAME', 'reservas');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

   `config.php` no se sube al repositorio (está en `.gitignore`).

4. **Arranca Apache y MySQL.** No hay nada más que ejecutar para la API.

5. **(Opcional) Instala Twig** para la web:

   ```bash
   composer install
   ```

6. **Pruébalo:**

   ```bash
   curl http://localhost/reservas/api.php/reservas
   ```

   Y la web en `http://localhost/reservas/index.php`.

### Endpoints

| Método | Ruta | Qué hace | Códigos |
|---|---|---|---|
| GET | `api.php/reservas` | Lista reservas. Filtros: `estado`, `desde`, `hasta`, `huesped` | 200, 400 |
| GET | `api.php/reservas/3` | Detalle de una reserva y su historial | 200, 400, 404 |
| POST | `api.php/reservas` | Crea una reserva (JSON en el cuerpo) | 201, 400, 500 |
| PUT | `api.php/reservas/3/estado` | Confirma o cancela (`{"estado":"confirmada"}`) | 200, 400, 404, 409, 500 |

Cualquier otra ruta devuelve 404 y un método no permitido devuelve 405. Todas las respuestas son JSON.

Los filtros se pueden combinar, por ejemplo `api.php/reservas?estado=pendiente&huesped=ana`.

## Estructura y decisiones

```
reservas/
├── api.php                  router de la API
├── index.php, detalle.php,
│   nueva.php                páginas de la web
├── config.php               datos de la BD (no se sube)
├── database.sql             tablas y datos de ejemplo
├── Controller/
│   ├── ReservaController.php   recibe la petición, valida y responde JSON
│   └── VistaController.php     lo mismo pero pintando HTML con Twig
├── Model/
│   ├── ReservaDB.php           conexión a la base de datos (PDO)
│   ├── Reserva.php             reservas: consultas y validación
│   └── ReservaEvento.php       historial de cada reserva
└── View/                    plantillas Twig
```

**Por qué así.** Cada clase hace una sola cosa:

- El **router** (`api.php`) solo decide a qué método llamar según la URL y el método HTTP.
- El **controller** valida lo que llega y decide el código HTTP. No toca SQL.
- El **model** habla con la base de datos y no hace `echo` ni `header`. Devuelve objetos, `null` o `true`/`false`, y el controller decide qué responder.
- La conexión está en su propia clase para no repetirla.

Solo hay un controller para la API porque hay un único recurso (la reserva) y cuatro operaciones; separarlo más habría sido complicar sin ganar claridad.

**Por qué MySQL.** Los datos son relacionales (una reserva tiene muchos eventos de historial) y hace falta filtrar por estado y fechas. Además es la base de datos que he usado durante mi etapa de formación, trabajando en `localhost`, así que me ha resultado sencillo montarla y configurarla.

**Seguridad:**

- Todas las consultas usan sentencias preparadas con PDO (nada de concatenar variables en el SQL).
- Todo lo que llega se valida en el servidor: campos obligatorios, email, fechas que existan y salida posterior a entrada, importe numérico y estado permitido.
- Los errores internos no se enseñan al cliente: se guardan en el log y se responde un 500 genérico.
- La web escapa los datos con el autoescape de Twig (contra XSS) y las credenciales están fuera del repositorio.
- No se puede modificar una reserva cancelada (409).

**Dificultades y despliegue:**
Lo que más me ha costado

Twig. Es lo que más me ha costado, porque apenas lo había tocado antes. Me ha tocado entender cómo se separan las plantillas del controller, cómo se pasan los datos y cómo funcionan extends y los bloques. Lo he podido sacar adelante y la web funciona, aunque la parte importante de la prueba es la API.

Las respuestas JSON y los códigos de error. En clase estaba acostumbrado a hacer die("mensaje") o redirigir con header("Location..."). Aquí había que responder siempre en JSON con el código HTTP correcto (200, 201, 400, 404, 405, 409, 500), validar antes de actuar y no enseñar nunca errores internos. Me ha costado cambiar esa forma de pensar, pero ahora tengo una única función responder() que lo hace en un solo sitio.

El despliegue en remoto y la base de datos en la nube (Aiven con HeidiSQL). Subir el proyecto al servidor mediante FileZilla requirió ajustar bien las rutas del servidor web (/var/www/html/...). Además, al no usar una base de datos local del servidor, tuve que configurar una instancia externa gratuita en Aiven, conectar un cliente gráfico como HeidiSQL para gestionar las credenciales, crear la base de datos (reservas) e importar el archivo database.sql manualmente. Esto me obligó a depurar errores de conexión remota, adaptar el fichero config.php en producción con los parámetros de la nube y asegurar que las tablas se creasen en el entorno correcto para que la API dejara de arrojar errores de tipo Table doesn't exist.

Lo que me ha resultado fácil

La lógica general de la base de datos y la estructuración del código en PHP puro, ya que los conceptos de PDO y control de modelos se asemejan mucho a lo trabajado durante mi formación en localhost.

**Dónde está desplegado:**

La aplicación está subida por FTP a un servidor que nos ha facilitado la institución donde me he formado. Allí están la API y la web.

URL de la API: http://ruix.iesruizgijon.es/sedahbani/reserva/api.php/reservas

URL de la web: http://ruix.iesruizgijon.es/sedahbani/reserva/index.php

Para subirlo hay que subir todo el proyecto menos config.php (que en el servidor se crea con los datos de su base de datos) y, si se usa la web, la carpeta vendor/ generada con composer install, ya que Twig no está en el repositorio.
## Qué haría diferente con más tiempo

- **Usar transacciones.** Ahora, al crear una reserva o cambiar su estado, primero se guarda la reserva y luego el evento del historial. Si falla el segundo paso, la reserva queda guardada sin historial. Con `beginTransaction`/`commit` se guardarían las dos cosas o ninguna.
- **Tests con PHPUnit** de la validación y de los endpoints principales.
- **Autenticación.** Ahora cualquiera puede crear o cancelar reservas. Haría falta un login o una API key.
- **Paginación** en el listado.
- **Comprobar solapamientos:** que no se pueda reservar el mismo alojamiento en fechas que ya están ocupadas.
- **Un router más limpio** y manejo de errores centralizado.
- **Docker Compose** para levantar PHP, Apache y MySQL con un solo comando.
- **Que los datos de ejemplo y las transiciones de estado** (pendiente → confirmada → cancelada) estén mejor definidos.

## Escalabilidad: 1000 reservas/hora

1000 reservas por hora son menos de una por segundo, así que con una sola máquina y una base de datos bien indexada no habría problema. Lo que cambiaría para que aguantara y fuera robusto:

1. **Índices en la base de datos** en `estado`, `fecha_entrada`, `fecha_salida` y `nombre_huesped`, que son las columnas por las que se filtra. Sin ellos, cada listado recorre toda la tabla.
2. **Paginación obligatoria** en el listado. Ahora devuelve todas las reservas y, con miles de filas, sería lento y pesado.
3. **Transacciones y control de concurrencia.** Con mucho tráfico, dos personas pueden reservar el mismo alojamiento a la vez. Se evitaría comprobando el solapamiento dentro de una transacción con bloqueo de filas (`SELECT ... FOR UPDATE`).
4. **Reutilizar la conexión.** Ahora cada consulta abre una conexión nueva. Pasaría a una sola conexión por petición, o conexiones persistentes.
5. **Caché** (por ejemplo Redis) para las consultas de lectura que más se repiten.
6. **Más de un servidor de aplicación** detrás de un balanceador. Como la API no guarda estado entre peticiones, es fácil de replicar. En la base de datos, una réplica de lectura para los listados.
7. **Tareas en segundo plano** (cola de mensajes) para lo que no tiene que bloquear la respuesta, como enviar emails de confirmación.
8. **Limitar peticiones (rate limiting)** y **logs y monitorización** para detectar errores y abusos.
9. **Archivar reservas antiguas** para que la tabla principal no crezca sin límite.
