# AXXER Academy

AXXER Academy é uma plataforma Laravel para ensino online, liberação de produtos e acompanhamento de progresso dos alunos.

A fase UX 2.x transforma a experiência do aluno em uma interface mobile-first, premium e focada em continuidade de estudo, sem alterar banco de dados, regras acadêmicas, autenticação, autorização, progresso ou regras do player.

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

## Papéis E Criadores

O sistema trabalha com três papéis:

- `admin`: administra toda a plataforma e visualiza todos os produtos, cursos, módulos e aulas;
- `creator`: administra somente cursos em que aparece como criador responsável;
- `aluno`: acessa apenas a experiência acadêmica liberada.

A propriedade individual dos cursos fica em `courses.creator_id`, uma chave estrangeira nullable para `users`. A relação é:

```text
User -> createdCourses
Course -> creator
```

Módulos e aulas não duplicam `creator_id`: a propriedade é derivada por `lesson -> module -> course -> creator_id`.

Cursos antigos ou criados sem criador continuam visíveis para administradores, mas não aparecem para creators. Creators não podem criar em nome de outro usuário, alterar `creator_id`, acessar curso alheio por URL forjada nem manipular `product_id`, `course_id`, `module_id` ou `lesson_id` pelo payload.

Administradores podem atribuir ou trocar o criador responsável de um curso, selecionando apenas usuários com papel `creator`.

## UX 2.x

A fase UX 2.x prioriza:

- experiência mobile-first;
- menos densidade visual;
- cards com hierarquia clara;
- botões compactos e acessíveis;
- foco no próximo passo do aluno;
- player como protagonista;
- currículo sempre visível no mobile, sem accordion recolhido;
- lista de aulas compacta para exibir mais conteúdo na primeira tela;
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

No mobile, a ordem da tela de aula é:

```text
Player -> Título -> Descrição -> Status -> Botões -> Progresso -> Conteúdo da aula
```

O conteúdo da aula permanece visível e compacto no mobile, sem accordion recolhido. No desktop, a lista volta para a lateral esquerda e permanece fixa durante a rolagem.

Links de aulas vindos de listas internas adicionam `?focus=player`. Ao carregar a nova aula, a página rola suavemente até o player considerando o cabeçalho fixo e remove o parâmetro da URL com `history.replaceState`. A rolagem respeita `prefers-reduced-motion`.

O player não usa overlay permanente, opacidade reduzida, filtro, pseudo-elemento, fundo escuro ou camada acima do iframe. Depois do carregamento, a área visível contém apenas o iframe e os controles nativos do provedor.

Para evitar corte dos controles nativos, o wrapper do iframe não usa `overflow: hidden`, não aplica `border-radius` com clipping e não usa `transform`, filtro ou opacidade sobre o player. Embeds do Google Drive recebem uma folga vertical no mobile para preservar a barra inferior e os botões nativos.

O escurecimento que aparece durante a reprodução de arquivos do Google Drive vem da interface interna renderizada pelo próprio Google dentro de um iframe cross-origin. A Academy não consegue alterar internamente poster, thumbnail, overlay, brilho, opacidade, loading ou controles desse player. A correção local consiste em não interferir no iframe e em não cortar a interface nativa.

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

## Upload De Vídeos

A criação e edição de aulas usam upload nativo de vídeo em uma requisição multipart comum. A fase atual não usa upload em partes/chunks; por isso o limite exibido na tela é configurável e começa em 500 MB por padrão, não 5 GB.

O formulário aceita MP4, MOV e WEBM com validação de extensão, MIME type e tamanho. O arquivo é movimentado pelo `UploadedFile`/filesystem do Laravel; o fluxo não usa base64, não serializa o vídeo e não lê o arquivo inteiro em memória.

Variáveis esperadas:

```dotenv
VIDEO_STORAGE_PATH=/caminho/absoluto/para/public_html/videos
VIDEO_PUBLIC_URL=https://dominio.example/videos
VIDEO_UPLOAD_MAX_MB=500
```

Essas variáveis não possuem valores reais versionados. Sem `VIDEO_STORAGE_PATH` ou `VIDEO_PUBLIC_URL`, o upload falha com mensagem clara em vez de gravar silenciosamente em `backend/public`, `storage/app/public` ou `backend/storage`.

O arquivo é armazenado por isolamento acadêmico e de criador:

```text
VIDEO_STORAGE_PATH/
  creator-{id}/
    curso-{id}/
      aula-{id}/
        titulo-da-aula-a1b2c3.mp4
```

O nome original nunca é usado no arquivo público. O sistema gera um slug a partir do título da aula, adiciona um sufixo único e salva metadados como provedor, caminho, nome original, tamanho, extensão e data de upload.

`video_url` permanece no banco para retrocompatibilidade com aulas antigas de YouTube e Google Drive. O player usa `Lesson::videoUrl()` para resolver automaticamente vídeos nativos da Hostinger e URLs externas legadas. Vídeos nativos usam `<video controls playsinline preload="metadata">`; provedores externos continuam usando iframe quando necessário.

O disco atual é `video_public`, configurado em `config/filesystems.php`. Em produção, a intenção operacional é apontar `VIDEO_STORAGE_PATH` para o diretório `public_html/videos` do domínio e `VIDEO_PUBLIC_URL` para a URL pública `/videos`, sem hardcode no código.

Na criação, a aula é criada primeiro para obter `lesson_id`; em seguida o arquivo vai para `creator-{id}/curso-{id}/aula-{id}` e os metadados são salvos. Se o upload ou a atualização de metadados falhar, o registro não fica apontando para vídeo inválido e o arquivo recém-enviado é removido.

Na edição sem troca de vídeo, os metadados existentes permanecem intactos. Na substituição, o novo arquivo é enviado primeiro; o banco é atualizado depois; o arquivo antigo só é removido após sucesso. Como as aulas usam soft delete, a exclusão da aula mantém o arquivo por segurança e restauração; uma varredura futura de órfãos pode remover arquivos de aulas definitivamente descartadas.

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
