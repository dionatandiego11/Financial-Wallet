# Financial Wallet - Laravel, SQLite & Docker

![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white) ![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white) ![SQLite](https://img.shields.io/badge/SQLite-003B57?style=for-the-badge&logo=sqlite&logoColor=white) ![Nginx](https://img.shields.io/badge/NGINX-009639?style=for-the-badge&logo=nginx&logoColor=white) ![Docker](https://img.shields.io/badge/Docker-2496ED?style=for-the-badge&logo=docker&logoColor=white) ![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white) ![Vite](https://img.shields.io/badge/Vite-646CFF?style=for-the-badge&logo=vite&logoColor=white)

Este projeto é uma aplicação de carteira financeira desenvolvida com PHP e Laravel, utilizando SQLite como banco de dados. Ele permite que usuários cadastrados realizem transferências de saldo entre si e façam depósitos em suas próprias contas. As operações são passíveis de reversão.

Este repositório inclui uma configuração Docker completa usando Nginx, PHP-FPM e Supervisor para criar um ambiente conteinerizado para a aplicação.

## Funcionalidades Principais

*   Cadastro de Usuários e Autenticação (Laravel Breeze).
*   Página Inicial descritiva com CTAs.
*   Dashboard com saldo e histórico de transações paginado.
*   Operações de Depósito e Transferência entre usuários.
*   Reversão de Transações a partir do dashboard.
*   Edição de Perfil do usuário.
*   Seção Administrativa (`/admin/users`) para listar usuários (protegida por middleware `admin`).

## Requisitos

*   Docker e Docker Compose (recomendado) ou apenas Docker Engine.
*   Git (para clonar o repositório).
*   Um navegador web.

## Instalação e Execução com Docker

Esta é a forma recomendada para executar a aplicação, pois encapsula todas as dependências.

1.  **Clonar o Repositório:**
    ```bash
    git clone https://github.com/dionatandiego11/financial-wallet.git
    cd financial-wallet
    ```

2.  **Copiar Arquivo de Ambiente:**
    O Dockerfile utilizará as configurações padrão, mas é bom ter o `.env` localmente para referência ou futuras customizações.
    ```bash
    cp .env.example .env
    ```
    *Nota: Para esta configuração Docker específica com SQLite, as variáveis `DB_*` no `.env` não são estritamente necessárias para o build/run inicial, pois a conexão SQLite é configurada para usar o arquivo dentro do container/volume.*

3.  **Criar Arquivo de Banco de Dados SQLite:**
    Embora o Laravel possa criar o arquivo, é mais seguro garantir que ele exista antes do build ou do primeiro run com volume.
    ```bash
    mkdir -p database
    touch database/database.sqlite
    ```

4.  **Construir a Imagem Docker:**
    Execute este comando na raiz do projeto (onde está o `Dockerfile`).
    ```bash
    sudo docker build -t financial-wallet-app .
    ```
    *(Use `sudo` se seu usuário não pertencer ao grupo `docker`. O nome `financial-wallet-app` é um exemplo).*
    *Nota: Se você encontrar erros de "no space left on device", limpe o espaço do Docker usando `sudo docker system prune -a -f --volumes`.*

5.  **Executar o Container Docker:**
    ```bash
    sudo docker run -d -p 8080:80 \
           --name wallet-container \
           -v "$(pwd)/database":/var/www/html/database \
           financial-wallet-app
    ```
    *   `-d`: Executa o container em segundo plano.
    *   `-p 8080:80`: Mapeia a porta 8080 do seu computador para a porta 80 do container (onde o Nginx está escutando). Acesse a aplicação em `http://localhost:8080`.
    *   `--name wallet-container`: Nomeia o container para facilitar o gerenciamento (`docker stop wallet-container`, `docker logs wallet-container`, etc.).
    *   `-v "$(pwd)/database":/var/www/html/database`: **Essencial para SQLite!** Monta a pasta `database` local dentro do container. Isso garante que o arquivo `database.sqlite` seja persistido no seu computador local, mesmo que o container seja removido e recriado.
    *   `financial-wallet-app`: O nome da imagem Docker que você construiu na etapa anterior.

6.  **Executar Migrações (Dentro do Container):**
    Após o container estar rodando, você precisa executar as migrações do Laravel *dentro* dele para criar as tabelas no arquivo SQLite.
    ```bash
    sudo docker exec wallet-container php artisan migrate --force
    ```
    *   `docker exec wallet-container`: Executa um comando dentro do container chamado `wallet-container`.
    *   `php artisan migrate`: O comando do Laravel para rodar as migrações.
    *   `--force`: É recomendado em scripts ou ambientes não interativos para evitar perguntas de confirmação (especialmente em produção, mas útil aqui também).

7.  **Acessar a Aplicação:**
    Abra seu navegador e vá para `http://localhost:8080`.

## Instalação e Configuração (Ambiente de Desenvolvimento Local - Sem Docker)

Se preferir rodar localmente sem Docker:

1.  **Requisitos:** PHP, Composer, Node/NPM, Extensão PHP SQLite.
2.  Clone o repositório: `git clone ... && cd ...`
3.  Instale dependências PHP: `composer install`
4.  Copie `.env.example` para `.env`: `cp .env.example .env`
5.  Gere a chave: `php artisan key:generate`
6.  Configure `.env` para SQLite e crie o arquivo:
    ```env
    DB_CONNECTION=sqlite
    ```
    ```bash
    touch database/database.sqlite
    ```
7.  Rode as migrações: `php artisan migrate`
8.  Instale dependências Node: `npm install`
9.  Compile assets e mantenha rodando: `npm run dev` (em um terminal)
10. Inicie o servidor Laravel: `php artisan serve` (em outro terminal)
11. Acesse `http://localhost:8000`.

## Configuração do Middleware de Admin

A rota `/admin/users` é protegida pelo middleware `admin`. Para que um usuário tenha acesso:

1.  **Defina a Lógica do Admin:** Edite o arquivo `app/Http/Middleware/IsAdminMiddleware.php`. Modifique a condição `if (...)` para identificar corretamente seus usuários administradores. Exemplo (usando e-mail):
    ```php
    // Dentro do método handle()
    if (Auth::check() && Auth::user()->email === 'seu-email-admin@example.com') {
        return $next($request);
    }
    ```
    Ou, se você adicionou um campo `is_admin` (BOOLEAN) à tabela `users`:
    ```php
    // Dentro do método handle()
    if (Auth::check() && Auth::user()->is_admin) { // Certifique-se de migrar se adicionar o campo
        return $next($request);
    }
    ```
2.  **Crie/Modifique o Usuário Admin:** Certifique-se de que existe um usuário no banco de dados que corresponda à condição definida acima. Você pode usar o registro normal ou criar via `php artisan tinker` (se rodando localmente) ou `docker exec wallet-container php artisan tinker` (se rodando com Docker).

## Estrutura do Banco de Dados (SQLite)

*   **`users`:** `id`, `name`, `email`, `password`, `balance` (REAL/NUMERIC), `is_admin` (BOOLEAN, opcional), `timestamps`.
*   **`transactions`:** `id`, `user_id`, `type` (TEXT), `amount` (REAL/NUMERIC), `related_user_id` (INTEGER, nullable), `original_transaction_id` (INTEGER, nullable), `description` (TEXT, nullable), `status` (TEXT), `timestamps`.
*   **Outras tabelas:** Padrão do Laravel/Breeze/Sanctum.

## Licença

[MIT license](https://opensource.org/licenses/MIT).
