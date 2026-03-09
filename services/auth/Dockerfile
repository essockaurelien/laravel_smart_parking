FROM php:8.3-cli

RUN apt-get update && apt-get install -y libpq-dev zip unzip \
    && docker-php-ext-install pdo_pgsql

WORKDIR /app
COPY . /app

EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
