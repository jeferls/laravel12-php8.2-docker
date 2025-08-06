FROM php:8.2-fpm

# Instala dependências do sistema e extensões PHP
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    librabbitmq-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    nodejs \
    npm \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        zip \
        gd \
        sockets \
    && printf "\n" | pecl install amqp \
    && docker-php-ext-enable amqp \
    && npm install -g pm2 \
    && rm -rf /var/lib/apt/lists/*

# Instala o Datadog PHP Tracer (detecta automaticamente a versão correta)
RUN curl -LO https://github.com/DataDog/dd-trace-php/releases/latest/download/datadog-setup.php \
    && php datadog-setup.php --php-bin php --tracer-version latest \
    && rm datadog-setup.php

# Instala o Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Define o diretório de trabalho
WORKDIR /var/www/html

# Copia código da aplicação
COPY . .

# Configura permissões e instala dependências PHP do projeto
RUN git config --global --add safe.directory /var/www/html \
    && [ -f .env ] || cp .env.example .env \
    && composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts \
    && mkdir -p storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 777 storage bootstrap/cache

# Copia configurações customizadas do PHP e entrypoint
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini

RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# Executa PHP-FPM
CMD ["php-fpm", "-F"]
