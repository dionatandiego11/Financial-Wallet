# Financial Wallet - Laravel & SQLite

![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white) ![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white) ![SQLite](https://img.shields.io/badge/SQLite-003B57?style=for-the-badge&logo=sqlite&logoColor=white) ![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white) ![Vite](https://img.shields.io/badge/Vite-646CFF?style=for-the-badge&logo=vite&logoColor=white)

Este projeto é uma aplicação de carteira financeira desenvolvida com PHP e Laravel, utilizando SQLite como banco de dados para simplicidade e portabilidade em ambientes de desenvolvimento. Ele permite que usuários cadastrados realizem transferências de saldo entre si e façam depósitos em suas próprias contas. As operações são passíveis de reversão.

## Funcionalidades Principais

*   **Cadastro de Usuários:** Permite que novos usuários criem suas contas.
*   **Autenticação:** Sistema de login seguro para acesso à carteira (via Laravel Breeze). Inclui links "Esqueci minha senha" e "Registrar" na tela de login.
*   **Gestão de Saldo:** Cada usuário possui um saldo em sua carteira.
*   **Página Inicial (Welcome):** Página de entrada com descrição da aplicação e CTAs para Login/Registro.
*   **Dashboard:** Exibe o saldo atual do usuário e um histórico paginado de transações.
*   **Depósito:** Usuários podem depositar fundos em suas contas através de um formulário dedicado.
    *   Depósitos são adicionados ao saldo, mesmo que este esteja negativo.
*   **Transferência:** Usuários podem enviar dinheiro para outros usuários cadastrados via e-mail.
    *   Validação de saldo suficiente antes de realizar a transferência.
    *   Impede transferência para o próprio usuário.
*   **Histórico de Transações:** Todas as operações (depósito, transferência enviada/recebida, reversão) são registradas e exibidas no dashboard.
*   **Reversão de Operações:** Depósitos e transferências (tanto enviadas quanto recebidas) podem ser revertidos diretamente pelo dashboard (sujeito a regras de autorização).
*   **Edição de Perfil:** Usuários podem atualizar suas informações de perfil e senha (via funcionalidade do Breeze).
*   **Listagem de Usuários (Admin):** Uma seção administrativa básica (`/admin/users`) para visualizar todos os usuários cadastrados (protegida por middleware `admin`).

## Requisitos do Sistema

*   PHP >= 8.1 (Verifique `composer.json` para a versão exata suportada pelo seu Laravel)
*   Composer 2.x
*   Node.js (v18+) & NPM
*   Extensão PHP para SQLite (`php-sqlite3` ou similar, geralmente habilitada por padrão na maioria das instalações PHP modernas)
*   Um servidor web (Opcional para desenvolvimento, pois `php artisan serve` pode ser usado)

## Instalação e Configuração (Ambiente de Desenvolvimento com SQLite)

1.  **Clonar o Repositório:**
    ```bash
    git clone https://github.com/dionatandiego11/financial-wallet.git
    cd financial-wallet
    ```

2.  **Instalar Dependências do PHP:**
    ```bash
    composer install
    ```

3.  **Copiar Arquivo de Ambiente:**
    ```bash
    cp .env.example .env
    ```

4.  **Gerar Chave da Aplicação:**
    ```bash
    php artisan key:generate
    ```

5.  **Configurar para SQLite:**
    *   Edite o arquivo `.env`.
    *   Certifique-se de que `DB_CONNECTION` está definido como `sqlite`:
        ```env
        DB_CONNECTION=sqlite
        ```
    *   **Banco de Dados SQLite:**
        *   Você pode deixar a linha `DB_DATABASE` comentada ou em branco. Por padrão, o Laravel criará (ou usará) um arquivo chamado `database.sqlite` dentro da pasta `database/`.
        *   Para garantir, você pode criar o arquivo manualmente antes de migrar:
            ```bash
            touch database/database.sqlite
            ```
        *   Se preferir especificar um caminho absoluto, use:
            ```env
            # Exemplo: DB_DATABASE=/caminho/completo/para/seu/banco.sqlite
            ```

6.  **Executar Migrações do Banco de Dados:**
    Este comando criará as tabelas `users`, `transactions`, `password_reset_tokens`, `failed_jobs`, `personal_access_tokens`, etc., no seu arquivo SQLite.
    ```bash
    php artisan migrate
    ```
    *(Nota: Se você receber um erro informando que o banco de dados não existe, verifique se o arquivo `database.sqlite` foi criado na pasta `database/` ou se o Laravel tem permissão de escrita nessa pasta).*

7.  **(Opcional) Popular o Banco:**
    Se você criou Seeders para dados iniciais:
    ```bash
    php artisan db:seed
    ```

8.  **Instalar Dependências do Node.js:**
    ```bash
    npm install
    ```

9.  **Compilar Assets e Manter em Desenvolvimento:**
    Abra um **novo terminal** e mantenha este comando rodando enquanto desenvolve. Ele compila o CSS/JS e atualiza automaticamente quando você faz alterações nos arquivos de front-end.
    ```bash
    npm run dev
    ```
    *(Para deploy em produção, você usaria `npm run build` uma única vez).*

10. **Iniciar o Servidor de Desenvolvimento:**
    Em outro terminal (diferente do `npm run dev`):
    ```bash
    php artisan serve
    ```
    A aplicação estará acessível, por padrão, em `http://localhost:8000`.

## Estrutura do Banco de Dados (SQLite)

*   **`users`:** Armazena informações do usuário. Campo chave: `balance` (REAL/NUMERIC).
*   **`transactions`:** Registra todas as operações financeiras. Campos chave: `type` (TEXT), `status` (TEXT), `amount` (REAL/NUMERIC), `related_user_id` (INTEGER, nullable), `original_transaction_id` (INTEGER, nullable).
*   **Outras tabelas:** Padrão do Laravel/Breeze/Sanctum.

## Lógica de Negócios Principal (`WalletService`)

A lógica central para operações financeiras está encapsulada em `App\Services\WalletService.php`. Este serviço garante a consistência dos dados usando transações de banco de dados (`DB::transaction()`) para todas as operações críticas (depósito, transferência, reversão).

## Autenticação e Autorização

*   **Autenticação:** Utiliza o **Laravel Breeze** (stack Blade + Alpine.js) para todo o fluxo de autenticação (registro, login, recuperação de senha, verificação de e-mail - se habilitada).
*   **Autorização:**
    *   Middleware `auth` e `verified` protegem as rotas da carteira e do perfil.
    *   Middleware `admin` customizado (`App\Http\Middleware\IsAdminMiddleware.php`, registrado em `bootstrap/app.php`) protege as rotas administrativas (`/admin/*`). **Importante:** A lógica dentro do `IsAdminMiddleware` para identificar um administrador (ex: verificar e-mail ou um campo `is_admin`) precisa ser configurada conforme a necessidade do seu projeto.

## Como Usar a Aplicação

1.  Após a instalação, acesse `http://localhost:8000`.
2.  Clique em "Register" ou acesse `http://localhost:8000/register` para criar uma conta. Crie pelo menos duas contas para testar as transferências.
3.  Faça login. Você será redirecionado para o Dashboard.
4.  Use os botões "Deposit Funds" e "Transfer Funds".
5.  Verifique o histórico de transações.
6.  Tente reverter transações clicando no botão "Reverse".
7.  Edite seu perfil em `/profile`.
8.  Para acessar a área administrativa (após configurar o `IsAdminMiddleware` e logar como admin), vá para `/admin/users`.


## Licença

[MIT license](https://opensource.org/licenses/MIT).
