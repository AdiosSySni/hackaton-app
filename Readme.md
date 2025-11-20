В папке .osp лежит конфигурационный файл настроенный под работу с моей версией openserver, там измените версию php при необходимости на нужную вам(обратная совместимость с более новыми версиями должна быть, со старыми не гарантирую)

## Структура проекта

```
therapy-app/
├── backend/                    # PHP REST API
│   ├── api/
│   │   └── index.php          # Главный файл API
│   ├── config/
│   │   └── database.php       # Конфигурация БД
│   ├── models/
│   │   ├── Therapist.php      # Модель терапевта
│   │   ├── Patient.php        # Модель пациента
│   │   └── Session.php        # Модель сессии
│   └── database.sql           # SQL схема базы данных
└── frontend/                   # JavaScript SPA
    ├── index.html             # Главная страница
    ├── css/
    │   └── styles.css         # Стили
    └── js/
        └── app.js             # Логика приложения
```

**Для Apache с mod_rewrite:**

** Уже создано, используется для перенаправления всех запросов на index.php

Создайте файл `backend/api/.htaccess`: 

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php/$1 [L,QSA]
```

## API машруты(endpoints)

### Авторизация

- `POST /api/login` - Вход в систему
- `POST /api/register` - Регистрация 
- `POST /api/logout` - Выход
- `GET /api/check-auth` - Проверка авторизации (в фронте реализовано, но не используется)

### Пациенты

- `GET /api/patients` - Получить всех пациентов
- `POST /api/patients` - Создать пациента
- `GET /api/patients/{id}` - Получить данные пациента
- `PUT /api/patients/{id}` - Обновить пациента (в фронте не реализовано)
- `DELETE /api/patients/{id}` - Удалить пациента (в фронте не реализовано)

### Сессии

- `GET /api/sessions?patient_id={id}` - Получить сессии пациента
- `POST /api/sessions` - Создать сессию 
- `PUT /api/sessions/{id}` - Обновить сессию (в фронте не реализовано)
- `DELETE /api/sessions/{id}` - Удалить сессию (в фронте не реализовано)

