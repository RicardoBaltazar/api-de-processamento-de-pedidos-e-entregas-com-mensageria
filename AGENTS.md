# AGENTS.md

Este arquivo serve para documentar regras, contextos e especificacoes tecnicas do sistema, com o objetivo de fornecer contexto para agentes de IA e desenvolvedores.

## Arquitetura e Regras de Criacao de Classes

- O sistema segue a arquitetura **MVC (Controllers de API) + Service + Repository + DTO**.
- **Controllers** devem ser o mais simples possivel, apenas recebendo requisicoes, repassando para os Services e retornando DTOs.
- Toda regra de negocio deve estar nas classes da camada **Service**.
- **Repositories** devem ser as unicas camadas que acessam o banco de dados (via EF Core / DbContext).
- **Controllers** nunca devem acessar o banco diretamente, nem retornar Entities da camada de dados; sempre retornar **DTOs**.
- Estrutura de pastas dentro de `Api/`:
    - `Controllers/` - controllers de API
    - `Services/` - regras de negocio
    - `Repositories/` - acesso a dados (EF Core)
    - `DTOs/` - objetos de transferencia de dados (request e response)
    - `Models/` - entidades do dominio (tabelas)
    - `Data/` - DbContext e configuracoes do EF Core
    - `Exceptions/` - excecoes customizadas e handlers globais
- Services podem ter metodos publicos e privados, mas a quantidade de metodos privados deve ser a menor possivel, apenas quando realmente necessario.
- O objetivo e garantir controllers e services enxutos, com repositories responsaveis pelo acesso a dados.

## Tratamento de Excecoes

- O padrao de tratamento de excecoes e um **middleware global** via `IExceptionHandler` (registrado com `AddExceptionHandler<T>()` no `Program.cs`).
- Para erros controlados (validacao, nao encontrado, conflito, etc.), lancar a excecao customizada `HttpException` de `Api/Exceptions/` com o status code e a mensagem desejados:

```csharp
throw new HttpException(StatusCodes.Status404NotFound, "Informação não encontrada.");
```

- O handler global (`GlobalExceptionHandler`) converte a excecao lancada em resposta **ProblemDetails** (RFC 7807):
    - `HttpException` -> status code definido na excecao
    - qualquer outra excecao -> status 500 (erro interno generico, sem vazar detalhes internos)
- Controllers nunca devem conter `try/catch`; o tratamento e centralizado no handler global.

## Padrao de Commits

Os commits devem seguir o padrao **Conventional Commits** em portugues.

**Preferir sempre uma unica linha de commit:**

```text
feat: implementa visualizacao por grupo com busca de representantes e sistema de bloqueio
```

**Usar topicos APENAS se absolutamente necessario:**

```text
feat: implementa visualizacao por grupo de empresas

- Adiciona busca de representantes de grupo
```

**Tipos de commit:**

- `feat`: nova funcionalidade
- `fix`: correcao de bug
- `refactor`: refatoracao de codigo
- `perf`: melhorias de performance
- `docs`: documentacao
- `style`: formatacao de codigo
- `test`: adicao ou correcao de testes
- `chore`: tarefas de manutencao

## Injeção de Dependencia

- Sempre registrar dependencias no container de DI do `Program.cs`, usando `AddScoped` (ou ciclo de vida adequado), e injeta-las via construtor nas classes. Nunca instanciar classes de producao com `new` diretamente. Isso garante melhor testabilidade e desacoplamento.

## Logs e LGPD

- Sempre que criar logs no projeto, evite registrar ou expor dados sensiveis de usuarios ou pacientes, como CPF, CNPJ, e-mails, nomes completos, enderecos ou qualquer informacao pessoal identificavel, em conformidade com a LGPD. Prefira logs genericos ou mascarados quando necessario para depuracao.

## Database e Migrations

- Nao consulte, leia ou manipule dados do banco diretamente (ex.: executar queries SQL, acessar o banco via ferramentas ou scripts). Qualquer consulta a dados deve ser feita apenas por endpoints/rotas da aplicacao.
- E permitido **criar** migrations e artefatos relacionados (ex.: `dotnet ef migrations add NomeMigration`), mas **nunca execute-las** no banco (ex.: `dotnet ef database update`).
- Se for necessario criar ou alterar tabelas, crie a migration correspondente ou gere o script SQL puro diretamente na conversa para que o desenvolvedor execute manualmente no banco.
- Nao salve scripts SQL em arquivos no projeto (ex.: `Api/Sql/`). Eles devem ser exibidos apenas no chat.
- Exemplo de formato:

```sql
ALTER TABLE tabela ADD COLUMN novo_campo VARCHAR(255) NULL;
```

## Regras de Endpoints

- Todos os novos endpoints devem ser criados em portugues (pt-br) na URL.

## Regras de Operacao do Agente

- Só realize sempre o que estiver no planejamento. Nunca faça nada que não foi planejado antes, seja edição de arquivos existentes, seja criação de novos arquivos e novas lógicas.
- Nao altere nenhum arquivo existente sem antes pedir aprovacao durante o planejamento.
- Sempre tente escrever o menos codigo possivel e da forma mais simples possivel, considerando a arquitetura que estamos usando.

## Regras de Comentarios

- Nao adicione comentarios soltos no codigo.
- Documente metodos publicos com XML doc comments (`/// <summary>`, `/// <param>`, `/// <returns>`), conforme o exemplo abaixo:

```csharp
/// <summary>
/// Retorna o inventario de emissoes de home-office para um grupo de empresas.
/// </summary>
/// <param name="cnpj">CNPJ da empresa matriz.</param>
/// <returns>DTO com o inventario consolidado.</returns>
```

- Nao deixe `using` no topo dos arquivos quando nao estiverem sendo utilizados no codigo.