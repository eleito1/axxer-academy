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

O player aceita URLs do YouTube (inclusive não listado) e arquivos compartilhados do Google Drive. Para o Drive, configure o arquivo como “Qualquer pessoa com o link — Visualizador”.

## Experiência e progresso do aluno

Ao abrir uma aula, o acesso é registrado em `lesson_progress`. Enquanto a página da aula estiver visível, a posição é sincronizada a cada 15 segundos e também ao sair. A aula pode ser concluída manualmente ou automaticamente quando a posição registrada alcançar 90% da duração cadastrada.

Ao reabrir um curso, o aluno é direcionado à última aula acessada. O YouTube recebe o segundo salvo no parâmetro de retomada; o Google Drive mantém a posição no AXXER Academy, mas o player incorporado do Drive não oferece controle externo confiável para buscar automaticamente esse segundo.

Com 100% das aulas publicadas concluídas, a interface habilita o estado “Certificado disponível”. A geração do arquivo do certificado permanece fora desta fase.

## Testes

Execute `php artisan test`. A suíte usa SQLite isolado e não altera o banco MySQL da aplicação.
