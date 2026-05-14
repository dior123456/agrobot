# AgroBot - Asistente Agrícola con IA

## Descripción

AgroBot es un asistente agrícola inteligente diseñado específicamente para pequeños agricultores de Guinea Ecuatorial. Utiliza inteligencia artificial para proporcionar respuestas sobre plagas, enfermedades, cultivos y consejos agrícolas, con soporte para múltiples idiomas locales (español, fang y bubi).

Este proyecto fue desarrollado para el Hackathon "IA para el Desarrollo" en mayo de 2026.

## Características Principales

- **Chatbot con IA**: Integración con OpenAI para respuestas inteligentes
- **Base de conocimiento local**: Respuestas predefinidas para consultas comunes
- **Panel de administración**: Gestión completa de usuarios, contenido y consultas
- **Soporte multiidioma**: Español, Fang y Bubi
- **Historial de consultas**: Seguimiento de todas las interacciones
- **Gestión de cultivos**: Información sobre cultivos locales
- **Base de datos de plagas**: Identificación y tratamiento de plagas y enfermedades
- **Sistema de consejos**: Recomendaciones agrícolas por categorías

## Tecnologías Utilizadas

### Frontend
- HTML
- CSS3
- JavaScript (Vanilla)

### Backend
- PHP 7.4+
- MySQL 5.7+
- PDO para conexiones de base de datos

### IA y APIs
- OpenAI GPT API
- RESTful API

### Infraestructura
- XAMPP (Apache + MySQL + PHP)
- UTF8MB4 para soporte completo de caracteres

## Requisitos del Sistema

- XAMPP 8.0+ o servidor web con PHP 7.4+
- MySQL 5.7+
- Navegador web moderno
- Conexión a internet (para API de OpenAI)
- API Key de OpenAI (opcional, pero recomendado)

## Instalación

### 1. Preparar el Entorno

1. Instala XAMPP desde [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Inicia Apache y MySQL desde el panel de control de XAMPP

### 2. Configurar el Proyecto

1. Clona o descarga el proyecto en `C:\xampp\htdocs\agrobot\`
2. Crea la base de datos ejecutando el archivo `backend/bdd/agrobot.sql` en phpMyAdmin o MySQL Workbench

### 3. Configurar la API de OpenAI (Opcional pero recomendado)

1. Obtén una API Key de OpenAI desde [https://platform.openai.com/api-keys](https://platform.openai.com/api-keys)
2. Edita el archivo `backend/php/api.php`
3. Reemplaza la línea con `define('sk-proj-...` con tu API key real

### 4. Configurar la Base de Datos

Si necesitas cambiar las credenciales de la base de datos, edita `backend/dao/Database.php`:

```php
private $host = "localhost";
private $port = 3306;
private $db_name = "agrobot";
private $username = "root";
private $password = "";
```

## Uso

### Acceder a la Aplicación

1. Abre tu navegador web
2. Ve a `http://localhost/agrobot/frontend/vista/index.html`

### Para Usuarios

1. Regístrate o inicia sesión con tu nombre y código
2. Escribe tus consultas sobre agricultura en el chat
3. Adjunta imágenes si es necesario para identificación de plagas
4. Recibe respuestas del asistente IA

### Para Administradores

1. Inicia sesión como administrador
2. Gestiona usuarios, respuestas, consultas, cultivos, plagas y consejos
3. Monitorea el historial y logs del sistema

## Estructura del Proyecto

```
agrobot/
├── backend/
│   ├── bdd/
│   │   └── agrobot.sql          # Esquema de base de datos
│   ├── dao/
│   │   └── database.php         # Clase de conexión a BD
│   └── php/
│       └── api.php              # API REST principal
├── frontend/
│   ├── controlador/
│   │   └── app.js               # Lógica JavaScript
│   ├── css/
│   │   └── styles.css           # Estilos CSS
│   └── vista/
│       └── index.html           # Interfaz principal
└── README.md                    # Este archivo
```

## API Endpoints

La API REST está disponible en `backend/php/api.php` con los siguientes endpoints principales:

- `GET/POST /api.php?action=login` - Autenticación
- `POST /api.php?action=register` - Registro de usuarios
- `POST /api.php?action=chat` - Consultas del chatbot
- `GET /api.php?action=get_users` - Lista de usuarios (admin)
- `POST /api.php?action=create_response` - Crear respuesta (admin)
- Y muchos más para gestión completa

## Base de Datos

El sistema utiliza MySQL con las siguientes tablas principales:

- `usuarios` - Usuarios del sistema
- `respuestas` - Base de conocimiento local
- `consultas` - Historial de consultas
- `cultivos` - Información de cultivos
- `plagas` - Plagas y enfermedades
- `consejos` - Consejos agrícolas

## Desarrollo

### Contribuir

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit tus cambios (`git commit -am 'Agrega nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Abre un Pull Request

### Próximas Mejoras

- [ ] Soporte offline para respuestas básicas
- [ ] Integración con WhatsApp
- [ ] Aplicación móvil nativa
- [ ] Análisis de imágenes con IA local
- [ ] Notificaciones push
- [ ] Dashboard con estadísticas

## Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo LICENSE para más detalles.

## Contacto

Proyecto desarrollado para el Hackathon "IA para el Desarrollo" - Mayo 2026

## Agradecimientos

- OpenAI por la API de GPT
- Comunidad de desarrolladores de Guinea Ecuatorial
- Organizadores del Hackathon "IA para el Desarrollo"