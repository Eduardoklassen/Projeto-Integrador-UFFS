# Controle de Estoque e Pedidos — Backend

API REST para gestão de estoque, pedidos e financeiro de uma pequena empresa.
Desenvolvida como Trabalho Integrador da disciplina **GEX613 — Programação II**
(Universidade Federal da Fronteira Sul, Ciência da Computação).

O sistema integra as frentes da disciplina: **Programação II** (a aplicação
completa — API REST e interface web) e **Banco de Dados** (modelo relacional
de 15 tabelas), aplicando arquitetura em camadas e separação de
responsabilidades.

O repositório reúne o **backend** (esta pasta) e o **frontend** consumidor da
API (HTML5, CSS3 e JavaScript, sem framework). A interface cobre login,
dashboard, os CRUDs, busca, ordenação, filtros e é responsiva para celular,
tablet e desktop.

> Para a fundamentação do projeto — decisões de modelagem, lógica e desafios —
> consulte o **RELATORIO.pdf**. Este README cobre a parte técnica: arquitetura,
> instalação e testes.

---

## Sumário

- [Visão geral](#visão-geral)
- [Tecnologias](#tecnologias)
- [Arquitetura](#arquitetura)
- [Modelo de dados](#modelo-de-dados)
- [Funcionalidades](#funcionalidades)
- [Segurança](#segurança)
- [Instalação](#instalação)
- [Testes](#testes)
- [Rotas](#rotas)

---

## Visão geral

O backend expõe uma **API REST** consumida pela interface web do projeto. Juntos,
cobrem o ciclo de uma empresa que compra materiais, fabrica, vende produtos e
controla seu caixa:

- **Cadastros:** usuários, clientes, fornecedores, produtos, materiais
- **Operações:** pedidos de venda (com baixa de estoque) e compras (com entrada
  de estoque)
- **Financeiro:** receitas, despesas e caixa
- **Controle:** autenticação por token, autorização por perfil e histórico de
  status dos pedidos

---

## Tecnologias

| Tecnologia | Uso | Por quê |
|---|---|---|
| **PHP 8** (POO) | Linguagem do backend | Sem framework, para exercitar os fundamentos (roteamento, PDO, middleware) |
| **MySQL / MariaDB** | Banco relacional | Modelo com integridade referencial (chaves estrangeiras, constraints) |
| **PDO** | Acesso ao banco | Prepared statements (proteção contra SQL injection) |
| **firebase/php-jwt** | Autenticação | Tokens JWT — autenticação stateless, sem sessão no servidor |
| **vlucas/phpdotenv** | Configuração | Credenciais fora do código, via arquivo `.env` |
| **Composer** | Dependências | Padrão do ecossistema PHP |

---

## Arquitetura

Organização em camadas, com separação de responsabilidades:

public/index.php          → ponto de entrada único; recebe toda requisição
   ↓
routes/api.php            → mapeia URL + método HTTP para um controller
   ↓
app/Middleware/           → valida o token (auth) e o perfil (admin/comum)
   ↓
app/Controllers/          → recebe a requisição, valida entrada, devolve JSON
   ↓
app/Models/               → consulta o banco via PDO (prepared statements)
   ↓
MySQL


Componentes de apoio em `app/Core/` (Router, Request, Database), `app/Helpers/`
(Response padroniza o JSON; Validator valida entradas) e `app/Services/`
(AuthService cuida do JWT).

**Fluxo de uma requisição protegida:** o cliente envia o token no header
`Authorization: Bearer <token>`; o middleware valida; se ok, o controller é
chamado; ele usa o model para acessar o banco e responde em JSON padronizado.

## Modelo de dados

15 tabelas, organizadas por módulo:

- **Acesso:** `usuario`
- **Comercial:** `cliente`, `endereco`, `produto`, `pedido`, `item_pedido`, `historico_status`
- **Estoque:** `fornecedor`, `material`, `compra`, `compra_material`
- **Composição:** `produto_material`
- **Financeiro:** `caixa`, `receita`, `despesa`

Relacionamentos muitos-para-muitos resolvidos com tabelas associativas:
`compra_material` (uma compra contém vários materiais) e `produto_material`
(um produto utiliza vários materiais).

> As decisões de modelagem (rastreabilidade do pedido, histórico de status,
> escolha de associativas) estão detalhadas no **RELATORIO.pdf**.

## Funcionalidades

**Autenticação e autorização:** login via JWT; dois perfis (`admin` e `comum`);
  rotas de cadastro e a gestão de usuários restritas a `admin`.
**CRUD** completo das entidades principais — incluindo a **gestão de usuários**
  (`/api/usuarios`), que permite ao administrador listar, criar, editar e
  remover contas. A senha nunca é devolvida pela API (as consultas de listagem
  selecionam apenas colunas públicas), e três regras protegem o sistema: não é
  possível excluir o último administrador, excluir a própria conta, nem rebaixar
  o último administrador a usuário comum.
**Controle de estoque automático:** registrar uma compra **soma** ao estoque
  dos materiais; criar um pedido **baixa** o estoque dos produtos.
**Histórico de status:** cada mudança de status de um pedido é registrada
  (status anterior, novo e data), permitindo reconstruir sua linha do tempo.
**Transações:** pedidos e compras são gravados em transação — ou tudo é
  salvo, ou nada (consistência garantida).
**Tratamento de erros amigável:** quando uma exclusão é barrada por integridade
  referencial (por exemplo, tentar remover um fornecedor que possui compras),
  a API não devolve um erro genérico. O controller intercepta a violação de
  chave estrangeira e responde com `409 Conflict` e uma mensagem clara
  ("Este fornecedor possui compras vinculadas e não pode ser excluído"),
  orientando o usuário sobre o motivo real.
**Dashboard:** contagens e resumo dos dados, atualizados dinamicamente a cada
  acesso.

---

## Segurança

Além da autenticação por token e da autorização por perfil, o backend adota
algumas defesas de uso corrente:

- **Prepared statements (PDO)** em todas as consultas, prevenindo SQL injection.
- **Senhas com hash** (`password_hash`, bcrypt) — nunca armazenadas ou
  trafegadas em texto puro.
- **Proteção contra força bruta no login:** um limitador por IP
  (`app/Helpers/RateLimiter.php`) bloqueia temporariamente novas tentativas
  após sucessivas falhas, respondendo `429 Too Many Requests`.
- **Respostas de erro sanitizadas:** falhas internas não expõem detalhes do
  sistema (consultas, caminhos de arquivo) ao cliente; o detalhe fica no log
  do servidor, e o usuário recebe uma mensagem genérica.

---

## Instalação

Requisitos: **PHP 8.0+** (extensões `pdo_mysql` e `zip`), **MySQL 8+** ou
**MariaDB 10.5+**, **Composer**.

```bash
# 1- Dependências
composer install

# 2- Ambiente
cp .env.example .env      # edite as credenciais do banco e o JWT_SECRET

#3- Banco de dados (importar migrations na ordem numerada + seed)
#Detalhes e comando mais explicados no COMO_RODAR.md

# 4- Servidor
php -S localhost:8000 -t public

## Testes

A API é testada com o **Insomnia**. Importe `insomnia_collection.json`
(**Import → From File**), execute o **Login** (`admin@local.com` / `admin123`),
copie o token retornado para a variável de ambiente `token`, e as demais
requisições estarão autenticadas.

Exemplo de login via curl:
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@local.com","senha":"admin123"}'
```
Roteiro sugerido: Login → criar material → criar compra (estoque sobe) →
criar pedido (estoque baixa, gera histórico) → mudar status (histórico cresce).

## Rotas

Padrão REST `/api/{recurso}` com CRUD. Recursos: `login`, `register`,
`usuarios`, `produtos`, `pedidos`, `clientes`, `fornecedores`, `materiais`,
`caixas`, `receitas`, `despesas`, `enderecos`, `compras`, `dashboard`.

- Leitura (`GET`): exige autenticação.
- Escrita em cadastros base (`produtos`, `fornecedores`, `materiais`): exige
  perfil `admin`.
- Gestão de usuários (`/api/usuarios`, todos os métodos): restrita a `admin`.
  Um usuário `comum` que tente acessar recebe `403 Acesso negado`.

Definição completa em `routes/api.php`.

## Autor

**Eduardo Klassen** — Matrícula 20240017565
Ciência da Computação, UFFS
Trabalho Integrador · GEX613 Programação II
Auxilio de Inteligência artificial - Claude Code em instruçôes e correções precisas