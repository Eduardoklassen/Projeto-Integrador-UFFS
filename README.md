# Controle de Estoque e Pedidos — Backend

API REST para gestão de estoque, pedidos e financeiro de uma pequena empresa.
Desenvolvida como Trabalho Integrador da disciplina **GEX613 — Programação II**
(Universidade Federal da Fronteira Sul, Ciência da Computação).

O sistema integra duas frentes da disciplina: **Programação II** (a aplicação),
**Banco de Dados** (modelo relacional de 15 tabelas)
(arquitetura em camadas e separação de responsabilidades).

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
- [Instalação](#instalação)
- [Testes](#testes)
- [Rotas](#rotas)

---

## Visão geral

O backend expõe uma **API REST** consumida por um front-end (a ser desenvolvido
na etapa seguinte do projeto). Cobre o ciclo de uma empresa que compra materiais, fabrica,
vende produtos e controla seu caixa:

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
  rotas de cadastro restritas a `admin`.
**CRUD** completo das entidades principais.
**Controle de estoque automático:** registrar uma compra **soma** ao estoque
  dos materiais; criar um pedido **baixa** o estoque dos produtos.
**Histórico de status:** cada mudança de status de um pedido é registrada
  (status anterior, novo e data), permitindo reconstruir sua linha do tempo.
**Transações:** pedidos e compras são gravados em transação — ou tudo é
  salvo, ou nada (consistência garantida).
**Dashboard:** contagens e resumo dos dados.

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
(**Import → From File**), execute o **Login** (`admin@local` / `admin123`),
copie o token retornado para a variável de ambiente `token`, e as demais
requisições estarão autenticadas.

Exemplo de login via curl:
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@local","senha":"admin123"}'
```
Roteiro sugerido: Login → criar material → criar compra (estoque sobe) →
criar pedido (estoque baixa, gera histórico) → mudar status (histórico cresce).

## Rotas

Padrão REST `/api/{recurso}` com CRUD. Recursos: `login`, `register`,
`produtos`, `pedidos`, `clientes`, `fornecedores`, `materiais`, `caixas`,
`receitas`, `despesas`, `enderecos`, `compras`, `dashboard`.

- Leitura (`GET`): exige autenticação.
- Escrita em cadastros base (`produtos`, `fornecedores`, `materiais`): exige
  perfil `admin`.

Definição completa em `routes/api.php`.

## Autor

**Eduardo Klassen** — Matrícula 20240017565
Ciência da Computação, UFFS
Trabalho Integrador · GEX613 Programação II
Auxilio de Inteligência artificial - Claude Code em instruçôes e correções precisas