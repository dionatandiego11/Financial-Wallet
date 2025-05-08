# --- Build Stage ---
# Usando uma imagem oficial do PHP com FPM como base
FROM php:8.2-fpm AS builder

# Argumentos (podem ser alterados no build)
ARG USER_ID=1000
ARG GROUP_ID=1000
ARG USERNAME=laraveluser

# Variáveis de ambiente
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV DEBIAN_FRONTEND=noninteractive

# Diretório de trabalho
WORKDIR /var/www/html

# Instalar dependências do sistema (Laravel + SQLite + Node/NPM)
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    zip \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libsqlite3-dev \
    nodejs \
    npm \
    && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Instalar extensões PHP necessárias
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo pdo_sqlite zip bcmath opcache

# Instalar Composer globalmente
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Criar usuário da aplicação (evita rodar como root no build)
RUN groupadd -g $GROUP_ID $USERNAME || true && \
    useradd -u $USER_ID -g $USERNAME -m -s /bin/bash $USERNAME || true

# Copiar arquivos de dependência PHP
COPY --chown=$USERNAME:$USERNAME composer.json composer.lock ./

# Instalar dependências PHP (sem dev para produção)
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Copiar arquivos de dependência do frontend
COPY --chown=$USERNAME:$USERNAME package.json package-lock.json ./

# Instalar dependências Node
RUN npm ci

# Copiar o restante do código da aplicação
COPY --chown=$USERNAME:$USERNAME . .

# Compilar assets para produção
RUN npm run build

# Otimizar Laravel para produção
RUN php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache


# --- Final Application Stage ---
# Usando imagem Alpine para menor tamanho
FROM php:8.2-fpm-alpine AS app

# Diretório de trabalho
WORKDIR /var/www/html

# Instalar dependências de runtime (Nginx, Supervisor, SQLite)
RUN apk update && apk add --no-cache \
    nginx \
    supervisor \
    sqlite-libs \
    sqlite-dev \
    && rm -rf /var/cache/apk/*

# Instalar extensões PHP essenciais para runtime
RUN docker-php-ext-install pdo pdo_sqlite bcmath opcache

# Copiar aplicação do estágio 'builder'
COPY --from=builder /var/www/html/vendor ./vendor
COPY --from=builder /var/www/html/public ./public
COPY --from=builder /var/www/html/resources ./resources
COPY --from=builder /var/www/html/routes ./routes
COPY --from=builder /var/www/html/storage ./storage
COPY --from=builder /var/www/html/bootstrap/cache ./bootstrap/cache
COPY --from=builder /var/www/html/config ./config
COPY --from=builder /var/www/html/.env.example ./.env.example
COPY --from=builder /var/www/html/artisan ./artisan
COPY --from=builder /var/www/html/composer.json ./composer.json

# Copiar banco SQLite se estiver disponível
COPY --from=builder /var/www/html/database/database.sqlite ./database/database.sqlite

# Copiar configurações personalizadas
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Criar diretórios de log e garantir existência
RUN mkdir -p /var/log/supervisor /var/log/nginx /run/nginx /run/php && \
    touch /var/log/nginx/access.log /var/log/nginx/error.log && \
    chown -R www-data:www-data /run/nginx /run/php

# Permissões corretas para storage, cache e logs
RUN mkdir -p database && \
    chown -R www-data:www-data storage bootstrap/cache database && \
    chmod -R 775 storage bootstrap/cache database && \
    if [ -f database/database.sqlite ]; then \
        find database -type f -name '*.sqlite' -exec chmod 664 {} \; ; \
    fi && \
    chown -R www-data:www-data /var/log/nginx /var/log/supervisor

# Expor porta 80 (Nginx)
EXPOSE 80

# Comando de inicialização via Supervisor (gerencia Nginx e PHP-FPM)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
