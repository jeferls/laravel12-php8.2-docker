.PHONY: up down logs migrate

up:
	docker compose up -d --build

down:
	docker compose down

logs:
	docker compose logs -f

migrate:
	docker compose exec app php artisan migrate
