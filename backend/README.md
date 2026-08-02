# AXXER Academy

AXXER Academy é uma plataforma Laravel para ensino online, liberação de produtos e acompanhamento de progresso dos alunos.

A fase UX 2.0 transforma a experiência do aluno em uma interface mobile-first, premium e focada em continuidade de estudo, sem alterar banco de dados, regras acadêmicas, autenticação, autorização, progresso ou player.

## Requisitos

- PHP 8.2 ou superior com `pdo_mysql`, `mbstring`, `openssl`, `fileinfo` e `zip`
- Composer 2
- Node.js e npm
- MySQL 8 para ambiente local operacional

## Preparação Local

1. Instale dependências PHP:

   ```bash
   composer install
   ```

2. Instale dependências de frontend:

   ```bash
   npm install
   ```

3. Crie o `.env` local:

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Configure o banco local no `.env`.

5. Para uma instalação nova, rode migrations e seeds iniciais somente no ambiente local previsto:

   ```bash
   php artisan migrate --seed
   ```

6. Suba a aplicação:

   ```bash
   php artisan serve
   npm run dev
   ```

Também existe o script combinado:

```bash
composer run dev
```

## Atalhos Locais

Opcionalmente, configure aliases no seu shell:

```bash
alias academy-up='cd /Users/marcelo/projetos/axxer-academy/backend && composer run dev'
alias academy-down='echo "Encerre os processos do academy-up com Ctrl+C no terminal onde eles estão rodando."'
```

`academy-up` inicia servidor Laravel, fila, logs e Vite pelo script `composer run dev`.

`academy-down` é documentado como orientação segura para evitar encerrar processos indevidos automaticamente.

## Estrutura

```text
backend/
  app/
    Http/Controllers/
    Http/Requests/
    Models/
    Policies/
    Services/
    Support/
  database/
    migrations/
    seeders/
  resources/
    views/
      academy/
      admin/
      auth/
      components/
  routes/
  tests/
```

## Fluxo Acadêmico

A hierarquia principal é:

```text
Produto -> Curso -> Módulo -> Aula
```

Produtos controlam acesso. Cursos e aulas podem ficar publicados ou em rascunho. Alunos visualizam apenas produtos liberados, cursos publicados e aulas publicadas.

O cadastro público cria alunos com status `pendente`. Usuários `aprovado` acessam o dashboard. Usuários `pendente` ou `bloqueado` ficam na tela de status.

## UX 2.0

A fase UX 2.0 prioriza:

- experiência mobile-first;
- menos densidade visual;
- cards com hierarquia clara;
- botões grandes e acessíveis;
- foco no próximo passo do aluno;
- player como protagonista;
- currículo em accordion no mobile;
- sidebar elegante no desktop;
- empty states melhores;
- microinterações discretas;
- preparação para Dark Mode via tokens CSS.

## Design System

O layout base define tokens e componentes reutilizáveis:

- cores: `--brand`, `--surface`, `--text`, `--muted`, `--line`, `--ok`, `--danger`;
- espaçamentos e raios: `--radius`, `--radius-lg`, `--container`;
- componentes CSS: `.btn`, `.card`, `.soft-card`, `.badge`, `.chip`, `.progress-track`, `.course-card`, `.empty-state`;
- componentes Blade:
  - `x-ui.button`
  - `x-ui.progress`
  - `x-ui.empty-state`
  - `x-ui.section-header`

As views antigas continuam compatíveis com classes legadas como `.card`, `.btn`, `.grid`, `.badge`, `.muted`, `.actions` e `.user`, preservando telas administrativas sem refatoração forçada.

## Dashboard Do Aluno

O dashboard mostra:

- saudação contextual;
- progresso geral;
- última aula assistida;
- botão `Continuar assistindo`;
- cursos disponíveis;
- produtos liberados;
- cursos em andamento;
- cursos concluídos.

Quando não há conteúdo ou progresso, a interface mostra empty states específicos em vez de blocos vazios.

## Player

O vídeo ocupa a largura principal disponível e mantém proporção estável para evitar layout shift.

No mobile, o conteúdo do curso fica em accordion. No desktop, o currículo permanece lateral e fixo durante a rolagem.

O salvamento de progresso continua igual:

- sincronização a cada 15 segundos;
- sincronização ao ocultar a aba;
- sincronização ao sair da página;
- conclusão manual pelo botão;
- conclusão automática ao atingir 90% da duração cadastrada.

## Acessibilidade

A UX 2.0 inclui:

- link para pular ao conteúdo;
- foco visível;
- `aria-current="page"` na aula atual;
- rótulos de status para aulas atuais, concluídas e não iniciadas;
- `role="progressbar"` com valores;
- feedback de erro com `role="alert"`;
- status de sucesso com `role="status"`;
- contraste AA nos estados principais.

## Performance

As telas usam CSS local e componentes Blade simples, sem novas dependências JavaScript.

O player usa `aspect-ratio` para reduzir reflow e layout shift. Microinterações são feitas com transições curtas em CSS.

## Banco E Segurança

Não execute em produção:

- `migrate:fresh`
- `migrate:refresh`
- `migrate:reset`
- `migrate:rollback`
- `db:wipe`
- seeders fora do fluxo local previsto

O projeto possui proteção em `App\Support\DatabaseCommandGuard` para bloquear comandos destrutivos fora de testes isolados.

## Testes

A suíte usa SQLite em memória via `phpunit.xml`:

```dotenv
APP_ENV=testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

Execute:

```bash
php artisan test
```

## Formatação

Execute Laravel Pint:

```bash
./vendor/bin/pint
```

Para verificar sem alterar arquivos:

```bash
./vendor/bin/pint --test
```

## Checklist Antes De Commit

```bash
php artisan test
./vendor/bin/pint
git diff --check
git status --short --branch
```

Mensagem sugerida para esta fase:

```text
feat: redesign completo da experiência do aluno
```
