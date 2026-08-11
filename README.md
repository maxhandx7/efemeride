# Efemeride

Una app de Laravel 10 que se acuerda de los cumpleaños por ti y te escribe al WhatsApp antes de que sea tarde.
Corre en Coolify, usa tu WAHA para enviar y el task scheduler de Laravel para el reloj.

---

## Qué hace

- Guarda cumpleaños, aniversarios y fechas sueltas (renovar dominio, pagar el hosting, lo que sea).
- Avisa varias veces por evento: una semana antes, un día antes y el mismo día. Configurable por fecha.
- Manda el mensaje por WhatsApp a tu número o a un grupo, usando tu instancia de WAHA.
- Nunca repite un aviso, aunque el scheduler corra 96 veces al día (hay un candado en base de datos).
- Reintenta si WAHA está caído: 1 min, 5 min, 15 min. Después se rinde y lo deja anotado.
- Resumen semanal de lo que viene en los próximos 30 días.
- Responde comandos por WhatsApp: escribes *hoy*, *proximos* o *agregar Marcela 12/05* y contesta.
- Vigila que la sesión de WhatsApp no se haya desconectado sin avisar.
- Opcional: que Claude redacte el mensaje usando las notas de la persona.
- Fechas sin año (cuando no sabes cuántos cumple) y los del 29 de febrero, resueltos.

---

## Instalación

Estos archivos son las piezas propias del proyecto. El esqueleto de Laravel lo pones tú:

```bash
composer create-project laravel/laravel:^10.0 efemeride
cd efemeride
```

Luego copia encima las carpetas de este paquete (`app/`, `config/`, `database/`, `resources/`, `routes/`,
`Dockerfile`, `docker-compose.yml`, `.env.example`, `tests/`).

```bash
cp .env.example .env
php artisan key:generate
touch database/database.sqlite      # en local
php artisan migrate --seed
php artisan serve
```

Abre `http://localhost:8000/fechas`.

### Probar sin esperar a mañana

```bash
php artisan reminders:dispatch --dry-run              # ¿qué se enviaría hoy?
php artisan reminders:dispatch --dry-run --date=2026-03-07   # simula otro día
php artisan reminders:dispatch --force                # ignora la hora y manda ya
php artisan reminders:digest --dry-run                # ver el resumen semanal
php artisan waha:status                               # ¿sigue conectado el teléfono?
php artisan events:import contactos.csv               # importar en lote
```

CSV de ejemplo:

```csv
nombre,fecha,tipo,chat_id,notas
Mama,1968-03-14,birthday,,Le gustan las flores amarillas
Sara,12/05,birthday,,
Renovar dominio,21/11,custom,,
```

---

## Configurar WAHA

En el `.env`:

```env
WAHA_BASE_URL=http://waha:3000
WAHA_API_KEY=tu-api-key
WAHA_SESSION=default
WAHA_DEFAULT_CHAT_ID=573001112233@c.us
```

El `chatId` es tu número con código de país y el sufijo `@c.us`. Para grupos es `@g.us`.
Si escribes solo `3001112233` en un evento, la app le pega el `57` sola.

### Para que el bot conteste (opcional)

En el dashboard de WAHA, en la sesión, agrega un webhook:

- URL: `https://fechas.tudominio.com/webhooks/waha?token=EL_SECRETO`
- Evento: `message`

Y en el `.env`: `WAHA_WEBHOOK_SECRET=EL_SECRETO` más `WAHA_ADMIN_NUMBERS=573001112233`
para que solo tú puedas darle órdenes.

---

## Desplegar en Coolify

1. Sube el repo a GitHub.
2. En Coolify: **New Resource → Docker Compose**, apunta al repo y usa el `docker-compose.yml` incluido.
3. Llena las variables de entorno (`WAHA_*`, `PANEL_PASSWORD`, etc.).
4. Si tu WAHA está en otro proyecto, ajusta `WAHA_NETWORK` con el nombre de la red donde vive,
   o simplemente pon `WAHA_BASE_URL` con su URL pública.
5. Deploy.

Levanta tres contenedores:

| Contenedor  | Qué hace                                                        |
|-------------|-----------------------------------------------------------------|
| `app`       | nginx + php-fpm, el panel web                                    |
| `scheduler` | `schedule:work`, mira el reloj cada minuto                       |
| `worker`    | `queue:work`, envía los mensajes y reintenta si algo falla       |

El volumen `efemeride-storage` guarda la base SQLite y los logs. No lo borres.

### Alternativa: un solo contenedor

Si prefieres el despliegue simple de Coolify (Dockerfile, sin compose), crea el recurso normal y añade
dos **Scheduled Tasks** en Coolify:

- `php artisan schedule:run` → cada minuto (`* * * * *`)
- Y en el `.env`, `QUEUE_CONNECTION=sync` para que los mensajes se envíen en el momento, sin worker.

Es menos elegante pero funciona.

---

## Cómo evita mandarte 96 mensajes iguales

El scheduler corre `reminders:dispatch` cada 15 minutos. En cada corrida:

1. Calcula, para cada evento y cada antelación, si la fecha objetivo cae hoy.
2. Compara la hora configurada del evento con la hora actual.
3. Intenta crear un registro en `reminder_logs` con la llave única
   `(evento, días de antelación, fecha de la ocurrencia)`.
4. Si el registro ya existía, no hace nada. Si es nuevo, encola el envío.

La base de datos es el candado. Aunque corran dos schedulers a la vez, el mensaje sale una sola vez.

---

## Estructura

```
app/
├── Console/Commands/
│   ├── DispatchDueReminders.php   El que decide qué toca hoy
│   ├── SendWeeklyDigest.php       El resumen de los lunes
│   ├── CheckWahaSession.php       ¿Sigue vivo WhatsApp?
│   └── ImportEvents.php           Importar CSV
├── Http/Controllers/
│   ├── EventController.php        CRUD del panel
│   └── WahaWebhookController.php  El bot que contesta
├── Jobs/SendWhatsappReminder.php  Envío con reintentos
├── Models/                        Event, ReminderRule, ReminderLog
└── Services/
    ├── WahaService.php            Cliente HTTP de WAHA
    └── MessageComposer.php        Arma el texto (plantilla o IA)
```

---

## Ideas para después

- Notificar también por Telegram o correo cuando WhatsApp esté caído.
- Sugerir regalos con IA a partir de las notas y un presupuesto.
- Sincronizar con Google Contacts para no escribir cumpleaños a mano.
- Botón "ya lo felicité" que se responde desde el propio WhatsApp.
- Multiusuario, si algún día esto deja de ser solo tuyo.
