# Сервіс для бронювання ресурсів

## Вимоги

- PHP 8.1
- Docker
- Symfony Server

## Встановлення

```bash
docker-compose up -d
symfony console doctrine:schema:update --force
symfony console doctrine:fixture:load -n
```
