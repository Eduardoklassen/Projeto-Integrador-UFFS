# COMO RODAR E TESTAR — Backend Controle de Estoque e Pedidos

Guia para colocar o backend no ar e testar a API. Funciona em **Windows, Linux
ou macOS**. Onde o comando muda conforme o sistema, há uma nota indicando a
alternativa.

---

## 1. Pré-requisitos

- **PHP 8.0+**
- **Composer**
- **MySQL 8.0+** ou **MariaDB**

A forma mais simples de ter PHP + MySQL no Windows é instalar o **XAMPP**
(https://www.apachefriends.org). O Composer é instalado à parte
(https://getcomposer.org).

Confirme as versões (em um terminal):
```
php --version
composer --version
mysql --version
```

> **Se algum comando "não é reconhecido":** o programa não está no PATH.
> No Windows com XAMPP, o PHP fica em `C:\xampp\php\php.exe` e o MySQL em
> `C:\xampp\mysql\bin\mysql.exe` — use o caminho completo no lugar de `php` /
> `mysql` nos comandos a seguir.

---

## 2. Extensões do PHP necessárias

O projeto precisa das extensões **pdo_mysql** (conexão com o banco) e **zip**
(usada pelo Composer). Verifique:
```
php -m
```
Procure por `pdo_mysql` e `zip` na lista.

> **Se faltar alguma:** abra o `php.ini` (o caminho aparece em `php --ini`),
> localize a linha `;extension=pdo_mysql` ou `;extension=zip` e remova o `;`
> do início. Salve e rode `php -m` de novo para confirmar.

---

## 3. Instalar as dependências

Na pasta do projeto:
```
composer install
```
Isso cria a pasta `vendor/` — **necessária**, pois a biblioteca de JWT
(autenticação) fica nela.

---

## 4. Configurar o .env

Copie o arquivo de exemplo e ajuste se necessário:

**Windows (PowerShell):**
```
Copy-Item .env.example .env
```
**Linux / macOS:**
```
cp .env.example .env
```

Conteúdo padrão (já pronto para XAMPP — root sem senha):
```
DB_HOST=localhost
DB_PORT=3306
DB_NAME=controle_estoque_pedidos
DB_USER=root
DB_PASS=

JWT_SECRET=troque_por_uma_string_aleatoria_longa
JWT_EXPIRES=3600
```

> Se o seu MySQL tiver senha no usuário root, preencha `DB_PASS=`.
> Se der "Falha na conexão", troque `DB_HOST=localhost` por `DB_HOST=127.0.0.1`.
> O `JWT_SECRET` pode ser qualquer string longa e aleatória.

---

## 5. Criar o banco de dados (15 tabelas)

O banco tem **15 tabelas**, criadas por scripts numerados em
`database/migrations/`. A ordem importa por causa das chaves estrangeiras.

### Opção A — phpMyAdmin (visual, recomendada para avaliação)

1. Com o XAMPP rodando, acesse http://localhost/phpmyadmin
2. Vá em **Importar** → **Escolher arquivo** e importe **um de cada vez**,
   nesta ordem:
   ```
   00_create_database
   01_create_usuarios_table
   02_create_clientes_table
   03_create_produtos_table
   04_create_pedidos_table
   06_create_fornecedores_table
   05_create_itens_pedido_table
   07_create_historico_status_table
   08_create_enderecos_table
   09_create_materiais_table
   10_create_compras_table
   11_create_compra_material_table
   12_create_produto_material_table
   13_create_caixa_table
   14_create_receitas_table
   15_create_despesas_table
   ```
3. Por fim, importe `database/seeds/dados_iniciais.sql` (dados de exemplo).

### Opção B — Linha de comando

**Linux / macOS** (terminal bash):
```bash
for f in 00_create_database 01_create_usuarios_table 02_create_clientes_table \
         03_create_produtos_table 04_create_pedidos_table 06_create_fornecedores_table \
         05_create_itens_pedido_table 07_create_historico_status_table \
         08_create_enderecos_table 09_create_materiais_table 10_create_compras_table \
         11_create_compra_material_table 12_create_produto_material_table \
         13_create_caixa_table 14_create_receitas_table 15_create_despesas_table; do
  mysql -u root < "database/migrations/$f.sql"
done
mysql -u root < database/seeds/dados_iniciais.sql
```

**Windows (PowerShell)** — o PowerShell não aceita o operador `<`, então use
`Get-Content`:
```powershell
$ordem = @(
  "00_create_database","01_create_usuarios_table","02_create_clientes_table",
  "03_create_produtos_table","04_create_pedidos_table","06_create_fornecedores_table",
  "05_create_itens_pedido_table","07_create_historico_status_table",
  "08_create_enderecos_table","09_create_materiais_table","10_create_compras_table",
  "11_create_compra_material_table","12_create_produto_material_table",
  "13_create_caixa_table","14_create_receitas_table","15_create_despesas_table"
)
foreach ($f in $ordem) { Get-Content "database/migrations/$f.sql" | mysql -u root }
Get-Content "database/seeds/dados_iniciais.sql" | mysql -u root
```
> No Windows com XAMPP, troque `mysql` por `C:\xampp\mysql\bin\mysql.exe`.
> Se o root tiver senha, acrescente `-p` após `-u root` (será solicitada).

### Conferir
No phpMyAdmin (ou via `SHOW TABLES;`) o banco `controle_estoque_pedidos` deve
ter **15 tabelas**: caixa, cliente, compra, compra_material, despesa, endereco,
fornecedor, historico_status, item_pedido, material, pedido, produto,
produto_material, receita, usuario.

> Rodar o seed mais de uma vez gera o erro "Duplicate entry 'admin@local'" —
> é normal, significa que o usuário admin já existe. Pode ignorar.

---

## 6. Subir o servidor

Na pasta do projeto:
```
php -S localhost:8000 -t public
```
Deve aparecer `Development Server (http://localhost:8000) started`.
Deixe a janela **aberta** (é o servidor). Para parar: `Ctrl+C`.

**Teste rápido no navegador:** http://localhost:8000/api/produtos
Deve retornar um JSON `"Token de autenticação não fornecido"` (401). Isso é o
**comportamento correto** — significa que o servidor está no ar e as rotas estão
protegidas. (Pelo navegador sempre dá 401, porque ele não envia o token de
login; o token só é enviado pelo Insomnia.)

---

## 7. Testar a API no Insomnia

1. Instale o Insomnia: https://insomnia.rest/download
2. **Import → From File →** selecione `insomnia_collection.json` (na raiz do
   projeto). Isso importa todas as requisições prontas.
3. **Login:** na pasta "1. Autenticação", abra **Login** (já vem com
   `admin@local` / `admin123`) e clique em **Send**. A resposta traz um `token`.
4. **Configurar o token:** copie o valor do `token` (texto longo, sem aspas).
   Clique em **Base Environment** (canto superior esquerdo) e cole em
   `"token": ""`, ficando `"token": "<colado_aqui>"`. As rotas protegidas passam
   a funcionar. (O token expira em 1 hora; refaça o login se necessário.)

### Roteiro de teste sugerido (cobre os requisitos)

| Ordem | Requisição | O que valida |
|---|---|---|
| 1 | Login | Autenticação JWT |
| 2 | Listar / Criar produto | CRUD + restrição admin |
| 3 | Criar material → Listar materiais | CRUD |
| 4 | Criar compra → Listar materiais | Estoque do material **aumenta** |
| 5 | Criar pedido | Estoque do produto **baixa**; registra quem lançou |
| 6 | Detalhar pedido | Traz itens + histórico de status |
| 7 | Mudar status → Detalhar pedido | Histórico ganha nova linha (de/para) |
| 8 | Criar receita / despesa | Movimentação financeira ligada ao caixa |
| 9 | Dashboard | Contagens e resumo |

---

## 8. Problemas comuns

| Sintoma | Causa | Solução |
|---|---|---|
| `php`/`mysql`/`composer` não reconhecido | Não está no PATH | Use o caminho completo (no XAMPP, `C:\xampp\...`) |
| `The '<' operator is reserved` | Usou `<` no PowerShell | Use `Get-Content arq.sql \| mysql` (Passo 5B) |
| `Falha na conexão com o banco` | Falta `pdo_mysql`, MySQL parado ou host | Ative `pdo_mysql`; inicie o MySQL; tente `127.0.0.1` no `.env` |
| `The zip extension ... missing` | Falta `zip` no PHP | Ative `extension=zip` no `php.ini` |
| `Class "...JWT" not found` | Faltou `composer install` | Rode o Passo 3 |
| 401 em todas as rotas | Token não colado ou expirado | Refaça o Login e cole o token (validade 1h) |
| Página/JSON em branco | Servidor sem `-t public` | Suba com `php -S localhost:8000 -t public` |
| `Cannot add foreign key` | Migrations fora de ordem | Siga a ordem do Passo 5 (06 antes da 05) |
| Porta 8000 ocupada | Outro processo | Use `php -S localhost:8080 -t public` |

---

## Estrutura do banco (15 tabelas)

- **Acesso:** usuario
- **Comercial:** cliente, endereco, pedido, item_pedido, produto, historico_status
- **Estoque:** fornecedor, material, compra, compra_material
- **Composição:** produto_material (materiais que compõem cada produto)
- **Financeiro:** caixa, receita, despesa

Tabelas associativas (relacionamentos muitos-para-muitos):
`compra_material` (uma compra contém vários materiais) e
`produto_material` (um produto utiliza vários materiais).
