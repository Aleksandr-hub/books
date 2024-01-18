# Назва проєкту Книги

Проєкт писався з використанням Symfony 6.2, php8.1, postgresql15, Composer, docker. У вас повине бути готове відповідне локальне середовище.

## Встановлення

1. Клонуйте репозиторій.
2. Якщо потрібно виконайте команду `symfony composer update`, для встановлення всіх додаткових пакетів.
3. Налаштуйте підключення до БД в .env, compose.override.yaml та compose.yaml.
4. Запустіть локальний сервер "symfony server:start -d"
5. Запустіть Docker Compose у фоновому режимі (-d): "docker-compose up -d"
6. Тепер можна запустити згенеровану міграцію, для оновлення схеми локальної бази даних:"symfony console doctrine:migrations:migrate"

## Опис структури даних

Формат запитів/відповідей у всіх методах - json
### API для створення авторів

#### route:
```
 /api/authors/create
```
#### Метод:
```
 POST
```
#### Тіло запиту:
```
{
    "lastName": "lastName",
    "firstName": "firstName",
    "middleName": "middleName"
}
```
#### Тіло відповіді:
```
{"message":"Author created successfully"}
```

### API для перегляду списку всіх авторів
#### route:
```
 /api/authors/list
```
#### Метод:
```
 GET
```
#### Тіло запиту:
```
 {
    "page": 1
}
```
#### Тіло відповіді:
```
{
    "authors": [
        {
            "id": 1,
            "firstName": "firstName",
            "lastName": "lastName",
            "middleName": "middleName"
        }
    ],
    "pagination": {
        "currentPage": 1,
        "totalPages": 1,
        "totalCount": 3
    }
}
```

### API для створення книг
#### route:
```
 /api/books/create
```
#### Метод:
```
 POST
```
#### Тіло запиту:
```
{
    "title": "BOOKtitle",
    "description": "nice book",
    "authors": [1,2], // array id_authors
    "publicationDate": "01.01.2020",
    "image": "https://freelance.today/uploads/images/00/07/62/2017/06/13/14c404.jpg" // url для завантаження картинки
}
```
#### Тіло відповіді:
```
{
    "book": {
        "id": 1,
        "title": "BOOKtitle",
        "description": "nice book",
        "image": "/uploads/images/14c404-65a92dcbafaed.jpg",
        "publicationDate": "2020-01-01T00:00:00+00:00",
        "authors": [
            {
                "id": 1,
                "lastName": "test",
                "firstName": "test",
                "middleName": "testtt"
            },
            {
                "id": 3,
                "lastName": "Іван",
                "firstName": "Петро",
                "middleName": ""
            }
        ]
    }
}
```

### API для перегляду списку всіх книг
#### route:
```
 /api/books/list
```
#### Метод:
```
 GET
```
#### Тіло запиту:
```
{
    "page": 1
}
```
#### Тіло відповіді:
```
{
    "books": [
        {
            "id": 1,
            "title": "book1",
            "description": "super book",
            "image": "/uploads/images/PNG-transparency-demonstration-1-65a8f09e31018.png",
            "publicationDate": "2024-01-18T00:00:00+00:00",
            "authors": [
                {
                    "id": 1,
                    "lastName": "test",
                    "firstName": "test",
                    "middleName": "testtt"
                },
                {
                    "id": 3,
                    "lastName": "Іван",
                    "firstName": "Петро",
                    "middleName": ""
                }
            ]
        },
        {
            "id": 2,
            "title": "book2",
            "description": "",
            "image": "/uploads/images/PNG-transparency-demonstration-1-65a8faf4001b3.png",
            "publicationDate": "2024-01-18T00:00:00+00:00",
            "authors": [
                {
                    "id": 2,
                    "lastName": "test2",
                    "firstName": "test2",
                    "middleName": ""
                },
                {
                    "id": 3,
                    "lastName": "Іван",
                    "firstName": "Петро",
                    "middleName": ""
                }
            ]
        }
    ],
    "pagination": {
        "currentPage": 1,
        "totalPages": 1,
        "totalCount": 2
    }
}
```

### API для пошуку книг за прізвищем автора
#### route:
```
 /api/books/search
```
#### Метод:
```
 GET
```
#### Тіло запиту:
```
{
    "authorLastName": "Іван"
}
```
#### Тіло відповіді:
```
{
    "books": [
        {
            "id": 2,
            "title": "book2",
            "description": "",
            "image": "/uploads/images/PNG-transparency-demonstration-1-65a8faf4001b3.png",
            "publicationDate": "2024-01-18T00:00:00+00:00",
            "authors": [
                {
                    "id": 2,
                    "lastName": "test2",
                    "firstName": "test2",
                    "middleName": ""
                },
                {
                    "id": 3,
                    "lastName": "Іван",
                    "firstName": "Петро",
                    "middleName": ""
                }
            ]
        },
        {
            "id": 1,
            "title": "book1",
            "description": "super book",
            "image": "/uploads/images/PNG-transparency-demonstration-1-65a8f09e31018.png",
            "publicationDate": "2024-01-18T00:00:00+00:00",
            "authors": [
                {
                    "id": 1,
                    "lastName": "test",
                    "firstName": "test",
                    "middleName": "testtt"
                },
                {
                    "id": 3,
                    "lastName": "Іван",
                    "firstName": "Петро",
                    "middleName": ""
                }
            ]
        }
    ]
}
```

### API для перегляду однієї книги
#### route:
```
 /api/books/{id} // id - ідентифікатор книги
```
#### Метод:
```
 GET
```
#### Тіло відповіді:
```
{
    "book": {
        "id": 1,
        "title": "BOOK666",
        "description": "nice book",
        "image": "/uploads/images/14c404-65a92dcbafaed.jpg",
        "publicationDate": "2020-01-01T00:00:00+00:00",
        "authors": [
            {
                "id": 1,
                "lastName": "test",
                "firstName": "test",
                "middleName": "testtt"
            },
            {
                "id": 2,
                "lastName": "test2",
                "firstName": "test2",
                "middleName": ""
            },
            {
                "id": 3,
                "lastName": "Іван",
                "firstName": "Петро",
                "middleName": ""
            }
        ]
    }
}
```

### API для редагування книги
#### route:
```
 /api/books/{id} // id - ідентифікатор книги
```
#### Метод:
```
 PUT
```
#### Тіло запиту:
```
{
    "title": "BOOKtitle",
    "description": "nice book",
    "authors": [1,2], // array id_authors
    "publicationDate": "01.01.2020",
    "image": "https://freelance.today/uploads/images/00/07/62/2017/06/13/14c404.jpg" // url для завантаження картинки
}
```
#### Тіло відповіді:
```
{
    "book": {
        "id": 1,
        "title": "BOOKtitle",
        "description": "nice book",
        "image": "/uploads/images/14c404-65a92dcbafaed.jpg",
        "publicationDate": "2020-01-01T00:00:00+00:00",
        "authors": [
            {
                "id": 1,
                "lastName": "test",
                "firstName": "test",
                "middleName": "testtt"
            },
            {
                "id": 3,
                "lastName": "Іван",
                "firstName": "Петро",
                "middleName": ""
            }
        ]
    }
}
```
