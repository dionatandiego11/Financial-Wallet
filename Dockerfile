# --- Build Stage ---
# Usando uma imagem oficial do PHP com FPM como base
FROM php:8.2-fpm AS builder

# Argumentos (podem ser alterados no build)
ARG USER_ID=1000
ARG GROUP_ID=1000
ARG USERNAME=laraveluser

# Variaveis de Ambiente
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV DEBIAN_FRONTEND=noninteractive

# Diretorios de trabalho
WORKDIR /var/www/html

# Instalar dependencias do sistema (comuns para Laravel + SQLite + Node/NPM)
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

# Instalar extensões PHP necessÃƒÂ¡rias
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo pdo_sqlite zip bcmath opcache

# Instalar Composer globalmente
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Criar ususario da aplicaÃ§Ã£o (para evitar rodar tudo como root no build)
# Adicionado '|| true' aos comandos groupadd e useradd
# para nÃ£o falhar se o grupo ou usuÃƒÂ¡rio jjÃ¡Â¡ existir durante o build.
RUN groupadd -g $GROUP_ID $USERNAME || true && \
    useradd -u $USER_ID -g $USERNAME -m -s /bin/bash $USERNAME || true

# Copiar arquivos de dependencia primeiro para aproveitar o cache do Docker
COPY --chown=$USERNAME:$USERNAME composer.json composer.lock ./
# Instalar dependencias PHP
# Use --no-dev para produÃƒÂ§ÃƒÂ£o
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Copiar arquivos de dependencia do frontend
COPY --chown=$USERNAME:$USERNAME package.json package-lock.json ./
# Instalar dependencias Node
RUN npm ci

# Copiar o restante do cÃ³digo da aplicaÃ§Ã£o
COPY --chown=$USERNAME:$USERNAME . .

# Compilar assets para produÃƒÂ§ÃƒÂ£o
RUN npm run build

# Otimizar Laravel para produÃ§Ã£o
RUN php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache


# --- Final Application Stage ---
# Usando uma imagem Alpine para menor tamanho
FROM php:8.2-fpm-alpine AS app

# Diretorio de trabalho
WORKDIR /var/www/html

# Instalar dependencias de runtime (Nginx, Supervisor, libs SQLite)
# www-data user/group jÃ¡, existe na imagem php-fpm-alpine
RUN apk update && apk add --no-cache \
    nginx \
    supervisor \
    sqlite-libs \
    sqlite-dev \
    && rm -rf /var/cache/apk/*

# Instalar extensÃµes PHP essenciais para runtime
RUN docker-php-ext-install pdo pdo_sqlite bcmath opcache

# Copiar a aplicaÃ§Ãµes construida do estagio 'builder'
# Copiar apenas o necessario para reduzir o tamanho
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
# Se o arquivo database.sqlite for gerado pela aplicaÃ§Ã£o e nÃ£o existir no build, esta linha pode ser removida
# ou condicionada. Se ele Ã© parte do cÃ³digo-base, mantenha.
COPY --from=builder /var/www/html/database/database.sqlite ./database/database.sqlite

# Copiar configuraÃ§Ãµes personalizadas
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Criar diretÃƒÂ³rios de log e garantir existencia
RUN mkdir -p /var/log/supervisor /var/log/nginx /run/nginx /run/php && \
    touch /var/log/nginx/access.log /var/log/nginx/error.log && \
    chown -R www-data:www-data /run/nginx /run/php

# Definir permisspermissÃµesÃƒÂµes corretas para storage, cache e logs
# Certificar que a pasta database existe antes de tentar dar permissÃƒÂ£o ao arquivo
RUN mkdir -p database && \
    chown -R www-data:www-data storage bootstrap/cache database && \
    chmod -R 775 storage bootstrap/cache database && \
    # A permissÃ£o do arquivo sqlite ÃƒÂ© delicada, 664 pode ser suficiente se o arquivo jÃ¡, existir
    # Se for criado pelo app, a permissÃƒÂ£o da pasta 775 deve permitir
    if [ -f database/database.sqlite ]; then find database -type f -name '*.sqlite' -exec chmod 664 {} \; ; fi && \
    chown -R www-data:www-data /var/log/nginx /var/log/supervisor


# Expor a porta 80 (usada pelo Nginx)
EXPOSE 80

# Comando para iniciar o Supervisor (que iniciar Nginx e PHP-FPM)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]