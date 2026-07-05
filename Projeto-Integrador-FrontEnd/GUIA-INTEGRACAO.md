
---

## ✅ MAPEADO E INTEGRADO

### Produtos (GET /api/produtos)
Resposta real:
```json
{ "sucesso": true, "mensagem": "OK", "dados": [
  { "id_produto": 2, "nome": "Produto B", "preco_final": "49.50",
    "estoque": 30, "observacao": "Item de exemplo", "ativo": 1,
    "criado_em": "2026-06-06 18:01:31" }
]}
```
Colunas da tela: Produto (nome) · Observação · Estoque · Preço final · Status (ativo) · Ações.
Aviso "Esgotado" quando estoque === 0. Integrado via Http.get real.

### Pedidos (GET /api/pedidos) — coletado, ainda não tem tela própria
```json
{ "id_pedido":1, "id_cliente":1, "id_usuario":1, "status":"pago",
  "data_pedido":"2026-06-06", "data_entrega":null, "valor_total":"39.80",
  "cliente_nome":"Cliente Exemplo", "usuario_nome":"Administrador" }
```
