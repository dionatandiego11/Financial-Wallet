# --- Fase de Build ---
# Usa imagem oficial do PHP com FPM como base
FROM php:8.2-fpm AS builder

# Argumentos configuráveis no momento do build
ARG USER_ID=1000
ARG GROUP_ID=1000
ARG USERNAME=laraveluser

# Variáveis de ambiente
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV DEBIAN_FRONTEND=noninteractive

# Define o diretório de trabalho
WORKDIR /var/www/html

# Instala dependências do sistema (Laravel + SQLite + Node/NPM)
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

# Instala extensões PHP necessárias
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install pdo pdo_sqlite zip bcmath opcache

# Copia o Composer globalmente
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Cria usuário da aplicação (evita rodar como root durante o build)
RUN groupadd -g $GROUP_ID $USERNAME || true && \
    useradd -u $USER_ID -g $USERNAME -m -s /bin/bash $USERNAME || true

# Copia o código-fonte da aplicação (inclui artisan, .env.example, database.sqlite etc.)
# Essa linha foi movida para antes do 'composer install' e 'npm ci'
COPY --chown=$USERNAME:$USERNAME . .

# Instala dependências PHP para produção (sem pacotes de desenvolvimento)
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Instala dependências Node.js
RUN npm ci

# Compila os assets para produção
RUN npm run build

# Otimiza a aplicação Laravel para produção
RUN php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache


# --- Fase Final da Aplicação ---
# Usa imagem Alpine para reduzir o tamanho final
FROM php:8.2-fpm-alpine AS app

# Define o diretório de trabalho
WORKDIR /var/www/html

# Instala dependências necessárias para execução (Nginx, Supervisor, SQLite)
RUN apk update && apk add --no-cache \
    nginx \
    supervisor \
    sqlite-libs \
    sqlite-dev \
    && rm -rf /var/cache/apk/*

# Instala extensões PHP essenciais para runtime
RUN docker-php-ext-install pdo pdo_sqlite bcmath opcache

# Copia arquivos da aplicação gerados na fase 'builder'
# Agora incluindo toda a pasta bootstrap
COPY --from=builder /var/www/html/vendor ./vendor
COPY --from=builder /var/www/html/public ./public
COPY --from=builder /var/www/html/resources ./resources
COPY --from=builder /var/www/html/routes ./routes
COPY --from=builder /var/www/html/storage ./storage
COPY --from=builder /var/www/html/bootstrap ./bootstrap
COPY --from=builder /var/www/html/bootstrap/cache ./bootstrap/cache
COPY --from=builder /var/www/html/config ./config
COPY --from=builder /var/www/html/.env.example ./.env.example
COPY --from=builder /var/www/html/artisan ./artisan
COPY --from=builder /var/www/html/composer.json ./composer.json

# Copia o banco SQLite (certifique-se de que o arquivo exista antes do build)
COPY --from=builder /var/www/html/database/database.sqlite ./database/database.sqlite

# Copia configurações personalizadas
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Cria diretórios necessários para logs e PID, e ajusta permissões
# Alteração: inclui /opt/docker/var/run para evitar erro de PID do PHP-FPM
RUN mkdir -p /var/log/supervisor /var/log/nginx /run/nginx /run/php /opt/docker/var/run && \
    touch /var/log/nginx/access.log /var/log/nginx/error.log && \
    chown -R www-data:www-data /run/nginx /run/php /opt/docker/var/run && \
    chown -R www-data:www-data storage bootstrap/cache database && \
    chmod -R 775 storage bootstrap/cache database && \
    if [ -f database/database.sqlite ]; then \
        find database -type f -name '*.sqlite' -exec chmod 664 {} \; ; \
    fi && \
    chown -R www-data:www-data /var/log/nginx /var/log/supervisor

# Expõe a porta 80 (Nginx)
EXPOSE 80

# Inicia Supervisor (gerencia Nginx e PHP-FPM)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

