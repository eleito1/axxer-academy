# AXXER Academy — base operacional V1

## Requisitos

- PHP 8.2 ou superior com `pdo_mysql`, `mbstring`, `openssl`, `fileinfo` e `zip`
- Composer 2
- MySQL 8

## Preparação

1. Crie o banco: `CREATE DATABASE axxer_academy CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
2. Ajuste no `.env` as variáveis `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` e `DB_PASSWORD`.
3. Troque `ADMIN_EMAIL` e `ADMIN_PASSWORD` antes do primeiro seed.
4. Execute `php artisan migrate --seed`.
5. Inicie com `php artisan serve`.

O cadastro público cria alunos com status `pendente`. Somente usuários `aprovado` acessam o dashboard. Usuários `bloqueado` ou `pendente` ficam na tela de status. O administrador inicial é criado pelo seeder e pode aprovar/bloquear alunos e sincronizar os produtos liberados.

## Estrutura acadêmica

O menu administrativo `Produtos` permite gerenciar a hierarquia Produto → Curso → Módulo → Aula. A ordem é definida por um número inteiro; cursos e aulas podem ficar em rascunho ou publicados. Alunos visualizam somente produtos liberados e conteúdo publicado.

## Criadores

O papel `creator` acessa a área `/creator` e administra somente os cursos em que `courses.creator_id` aponta para o próprio usuário. Administradores continuam usando `/admin` e veem todos os cursos, inclusive cursos sem criador responsável.

Cursos sem `creator_id` não aparecem para creators e não recebem atribuição automática. A propriedade de módulos e aulas é sempre derivada do curso pai; não há `creator_id` duplicado em módulos ou aulas.

Na criação de curso, o creator é vinculado automaticamente como responsável. Payload com `creator_id` enviado por creator é rejeitado. Administradores podem informar ou trocar o criador, mas apenas para usuários com papel `creator`.

As aulas usam upload nativo de vídeo em requisição multipart comum, sem chunks nesta fase. O limite padrão exibido na tela é 500 MB e pode ser ajustado por `VIDEO_UPLOAD_MAX_MB`, respeitando também `upload_max_filesize`, `post_max_size`, timeouts do PHP/PHP-FPM, servidor web, proxy, navegador e limites da hospedagem.

Configure o disco `video_public` fora do Git:

```dotenv
VIDEO_STORAGE_PATH=/caminho/absoluto/para/public_html/videos
VIDEO_PUBLIC_URL=https://dominio.example/videos
VIDEO_UPLOAD_MAX_MB=500
```

Em produção, a intenção é que `VIDEO_STORAGE_PATH` aponte para o diretório `public_html/videos` do domínio e que `VIDEO_PUBLIC_URL` sirva a mesma pasta por HTTPS. A aplicação Laravel pode estar em outra pasta; o disco suporta essa separação porque usa caminho absoluto configurado. Sem `VIDEO_STORAGE_PATH` ou `VIDEO_PUBLIC_URL`, o upload falha claramente e não usa fallback para `backend/public`, `storage/app/public` ou `backend/storage`.

O formulário aceita MP4, MOV e WEBM e armazena os arquivos em uma estrutura isolada por criador, curso e aula:

```text
VIDEO_STORAGE_PATH/creator-{id}/curso-{id}/aula-{id}/arquivo-gerado.mp4
```

O arquivo público recebe nome gerado a partir do título da aula com sufixo único; o nome original é salvo apenas como metadado. `video_url` permanece para retrocompatibilidade com aulas antigas e continua sendo resolvido pelo player junto com vídeos nativos.

Na substituição de vídeo, o arquivo novo é enviado antes de alterar o banco; se a atualização falhar, o arquivo novo é removido e o antigo continua válido. Após sucesso, o arquivo antigo é removido. Como a exclusão de aula é soft delete, o vídeo é mantido para permitir restauração; uma limpeza futura de órfãos deve tratar descartes definitivos.

URLs de capa e material complementar continuam sendo informadas manualmente.

O player aceita URLs do YouTube (inclusive não listado) e arquivos compartilhados do Google Drive. Para o Drive, configure o arquivo como “Qualquer pessoa com o link — Visualizador”.

O player do Google Drive é renderizado em iframe cross-origin. Escurecimento durante a reprodução, overlay interno, poster, thumbnail e controles nativos pertencem ao próprio Google Drive e não podem ser alterados pela Academy. A interface local deve apenas preservar espaço e evitar clipping do iframe, sem `overflow: hidden`, filtro, opacidade ou camada visual acima do player.

## Experiência e progresso do aluno

Ao abrir uma aula, o acesso é registrado em `lesson_progress`. Enquanto a página da aula estiver visível, a posição é sincronizada a cada 15 segundos e também ao sair. A aula pode ser concluída manualmente ou automaticamente quando a posição registrada alcançar 90% da duração cadastrada.

Ao reabrir um curso, o aluno é direcionado à última aula acessada. O YouTube recebe o segundo salvo no parâmetro de retomada; o Google Drive mantém a posição no AXXER Academy, mas o player incorporado do Drive não oferece controle externo confiável para buscar automaticamente esse segundo.

Com 100% das aulas publicadas concluídas, a interface habilita o estado “Certificado disponível”. A geração do arquivo do certificado permanece fora desta fase.

## Testes

Execute `php artisan test`. A suíte usa SQLite isolado e não altera o banco MySQL da aplicação.
