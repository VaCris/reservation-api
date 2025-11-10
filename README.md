<p align="center">
  <a href="https://studios-tkoh.azurewebsites.net/" target="_blank">
    <img src="https://drive.google.com/uc?export=view&id=1TuT30CiBkinh85WuTvjKGKN47hCyCS0Z" width="300" alt="Studios TKOH Logo">
  </a>
</p>


# 📅 Reservation API

**API REST de reservas empresariales de nivel de producción** con autenticación JWT, emails transaccionales, notificaciones en tiempo real y exportación de reportes.

[![GitHub](https://img.shields.io/badge/GitHub-VaCris%2Freservation--api-blue?style=flat-square&logo=github)](https://github.com/VaCris/reservation-api)
[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=flat-square&logo=php)](https://www.php.net/)
[![Symfony](https://img.shields.io/badge/Symfony-7.0-000000?style=flat-square&logo=symfony)](https://symfony.com/)
[![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)](LICENSE)

---

## ✨ Características Principales

### 🔐 Autenticación & Seguridad
- Autenticación con **JWT (RS256)**
- Tokens con expiración configurable
- Protección de endpoints por roles (Admin, Manager, User)
- Auditoría completa de acciones

### 📅 Gestión de Reservas
- CRUD completo con validaciones
- **Reservas recurrentes** (diarias, semanales, mensuales)
- Detección automática de conflictos
- Códigos de confirmación únicos
- Estados: Pending → Confirmed/Cancelled
- Metadata flexible por reserva

### 📧 Notificaciones
- **Emails transaccionales** vía Mailtrap
- Plantillas HTML profesionales
- Notifications al crear, confirmar y cancelar
- Sistema de reintentos asincrónico (Messenger)
- Registro de todas las notificaciones

### 📊 Analytics & Reportes
- Dashboard con **métricas en tiempo real**
- Top recursos y usuarios
- Gráficos por día y hora pico
- Tasa de cancelación
- Filtros avanzados por período

### 📤 Exportación de Datos
- **PDF** con diseño profesional
- **Excel (.xlsx)** con formateo
- **iCalendar (.ics)** compatible con Google Calendar, Outlook
- Filtros por fecha, estado, usuario, recurso

### 🔔 Notificaciones Tiempo Real
- **WebSocket con Mercure**
- Suscripción a tópicos específicos
- Alertas inmediatas de cambios
- Notificaciones cuando recurso se libera

---

## 🛠️ Stack Tecnológico

| Aspecto | Tecnología |
|--------|------------|
| **Backend** | Symfony 7.0, PHP 8.4 |
| **Database** | MySQL 8.0, Doctrine ORM |
| **Authentication** | JWT (lexik/jwt-authentication-bundle) |
| **Email** | Mailtrap SMTP, Swift Mailer |
| **Export** | DomPDF, PHPOffice Spreadsheet |
| **Real-time** | Mercure WebSocket |
| **Message Queue** | Doctrine Transport |
| **Validation** | Symfony Validator, Custom Strategies |

---

## 🚀 Instalación Rápida

### Requisitos
- PHP 8.4+
- MySQL 8.0+
- Composer
- Git

### Pasos

```


# 1. Clonar repositorio

git clone https://github.com/VaCris/reservation-api.git
cd reservation-api

# 2. Instalar dependencias

composer install

# 3. Configurar variables de entorno

cp .env .env.local

# Edita .env.local con tus valores

# 4. Crear base de datos

php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# 5. Generar claves JWT

php bin/console lexik:jwt:generate-keypair

# 6. Iniciar servidor

symfony server:start

# 7. En otra terminal, ejecutar worker de mensajes

php bin/console messenger:consume async -vv

# 8. En otra terminal, iniciar Mercure

cd mercure
./mercure.exe --addr :3000 --subscriber-jwt-key 'tu-clave' --publisher-jwt-key 'tu-clave'

```

---

## 📚 Endpoints Principales

### Autenticación
```

POST /api/v1/login
Content-Type: application/json

{
"email": "admin@empresa.com",
"password": "password123"
}

```

Respuesta:
```

{
"token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
"user": {
"id": 1,
"email": "admin@empresa.com",
"first_name": "Admin",
"roles": ["ROLE_ADMIN"]
}
}

```

### Reservas

#### Crear Reserva
```

POST /api/v1/reservations
Authorization: Bearer {token}
Content-Type: application/json

{
"resourceId": 45,
"startTime": "2025-11-25T09:00:00Z",
"endTime": "2025-11-25T10:00:00Z",
"notes": "Reunión de equipo",
"metadata": {"attendees": 8}
}

```

#### Listar Reservas
```

GET /api/v1/reservations?status=confirmed\&limit=20
Authorization: Bearer {token}

```

#### Crear Reserva Recurrente
```

POST /api/v1/reservations/recurring
Authorization: Bearer {token}

{
"resourceId": 45,
"startTime": "2025-11-25T09:00:00Z",
"endTime": "2025-11-25T10:00:00Z",
"pattern": {
"type": "weekly",
"interval": 1,
"daysOfWeek": ["monday", "wednesday"],
"endDate": "2025-12-31"
}
}

```

#### Confirmar Reserva
```

PATCH /api/v1/reservations/{id}/confirm
Authorization: Bearer {token}

```

#### Cancelar Reserva
```

DELETE /api/v1/reservations/{id}
Authorization: Bearer {token}

```

### Estadísticas

```

GET /api/v1/stats/dashboard?period=month
Authorization: Bearer {token}

```

Respuesta:
```

{
"period": "month",
"overview": {
"total_reservations": 59,
"confirmed_reservations": 42,
"pending_reservations": 15,
"cancelled_reservations": 2,
"cancellation_rate": 3.39
},
"top_resources": [...],
"top_users": [...],
"reservations_by_day": [...],
"peak_hours": [...]
}

```

### Exportación

```


# PDF

GET /api/v1/export/pdf?start_date=2025-11-01\&end_date=2025-11-30
Authorization: Bearer {token}

# Excel

GET /api/v1/export/excel?status=confirmed
Authorization: Bearer {token}

# iCalendar

GET /api/v1/export/ical?user_id=1
Authorization: Bearer {token}

```

### Notificaciones Tiempo Real

```

GET /api/v1/mercure/token
Authorization: Bearer {token}

```

---

## 📁 Estructura del Proyecto

```

reservation-api/
├── src/
│   ├── Controller/          \# REST endpoints
│   ├── Entity/              \# Modelos Doctrine
│   ├── Repository/          \# Consultas BD
│   ├── Service/             \# Lógica de negocio
│   ├── Strategy/            \# Validaciones por estrategia
│   ├── Event/               \# Event listeners
│   └── Dto/                 \# Data Transfer Objects
├── migrations/              \# Database migrations
├── tests/                   \# Tests automatizados
├── config/                  \# Configuración Symfony
├── var/                     \# Cache y logs
├── public/                  \# Entry point
└── .env                     \# Variables de entorno

```

---

## 🔧 Configuración

### `.env.local`

```

DATABASE_URL="mysql://user:password@localhost:3306/name_db"

JWT_SECRET_KEY="%kernel.project_dir%/config/jwt/private.pem"
JWT_PUBLIC_KEY="%kernel.project_dir%/config/jwt/public.pem"
JWT_TOKEN_TTL=3600

MAILER_DSN=smtp://api:token@live.smtp.mailtrap.io:587?encryption=tls

MERCURE_URL=http://localhost:3000/.well-known/mercure
MERCURE_PUBLIC_URL=http://localhost:3000/.well-known/mercure
MERCURE_JWT_SECRET="tu-clave-secreta"

```

---

## 🧪 Testing

```


# Ejecutar todos los tests

php bin/phpunit

# Test específico

php bin/phpunit tests/Controller/ReservationControllerTest.php

# Con coverage

php bin/phpunit --coverage-html var/coverage

```

---

## 📊 Arquitectura

### Patrón Strategy
Cada recurso puede tener una estrategia de validación diferente:

```

// CommonResourceStrategy: Validación básica
// HighSecurityStrategy: Requiere más datos
// MeetingRoomStrategy: Validaciones específicas para salas

```

### Message Queue
Los emails se envían de forma **asincrónica** via Doctrine Messenger:

```

Crear Reserva → Guardar BD → Meter en queue → Worker envía email

```

### Event Listeners
Se disparan eventos en cada acción:

```

ReservationCreatedEvent → EmailService → RealtimeService → AuditLog

```

---

## 🚢 Deploy a Producción

### Render (Recomendado)

```


# render.yaml

services:

- type: web
name: reservation-api
runtime: php
buildCommand: composer install \&\& php bin/console migrate
startCommand: symfony server:start
envVars:
    - key: DATABASE_URL
fromDatabase:
name: reservation_db
property: connectionString

```

### Railway / Heroku / AWS

Sigue la documentación de Symfony: https://symfony.com/doc/current/deployment.html

---

## 📝 API Documentation

Accede a la documentación interactiva:

```

http://localhost:8000/api/doc

```

Endpoints documentados con OpenAPI 3.0

---

## 🤝 Contribuir

1. Fork el proyecto
2. Crea una rama (`git checkout -b feature/AmazingFeature`)
3. Commit cambios (`git commit -m 'Add AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

---

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

---

## 👤 Autor

**VaCris**
- GitHub: [@VaCris](https://github.com/VaCris)

---

## 🙏 Agradecimientos

- Symfony por el framework
- Doctrine ORM
- Lexik JWT Authentication
- Mailtrap por el servicio de email
- Mercure por WebSocket

---

## 📞 Soporte

Para problemas o dudas:
- Abre un **Issue** en GitHub
- Revisa la **documentación** de Symfony
- Consulta la **API Reference**

---

**⭐ Si te fue útil, no olvides dejar una estrella!**

<p align="center">
  <sub>🛠️ Desarrollado con 💙 por <strong>Studios TKOH</strong></sub><br>
  <a href="https://studios-tkoh.azurewebsites.net/" target="_blank">🌐 studios-tkoh.azurewebsites.net</a>
</p>
