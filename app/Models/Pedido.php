<?php

namespace App\Models;

use App\Core\Database;
use PDO;
use RuntimeException;

  // O QUE mudou: o model anterior só tinha listar() e contar().
  // Agora cobre o ciclo inteiro: criar (com itens, baixa de
  // estoque e histórico), detalhar, mudar status e excluir.
  // transações: criar/excluir pedido tocam em
  // 3+ tabelas (pedido, item_pedido, produto, historico_status).
  // Sem transação, uma falha no meio deixaria estoque errado ou
  // pedido sem itens. 
 
class Pedido
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // Lista com nomes de cliente e responsável (o front exibe ambos)
    public function listar(): array
    {
        return $this->db->query(
            'SELECT p.*, c.nome AS cliente_nome, u.nome_usuario AS usuario_nome
             FROM pedido p
             JOIN cliente c ON c.id_cliente = p.id_cliente
             LEFT JOIN usuario u ON u.id_usuario = p.id_usuario
             ORDER BY p.id_pedido DESC'
        )->fetchAll();
    }

    //Detalhe: pedido + itens (com nome do produto) + histórico. 
    public function buscar(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*, c.nome AS cliente_nome, u.nome_usuario AS usuario_nome
             FROM pedido p
             JOIN cliente c ON c.id_cliente = p.id_cliente
             LEFT JOIN usuario u ON u.id_usuario = p.id_usuario
             WHERE p.id_pedido = :id'
        );
        $stmt->execute(['id' => $id]);
        $pedido = $stmt->fetch();
        if (!$pedido) {
            return null;
        }

        $stmt = $this->db->prepare(
            'SELECT i.*, pr.nome AS produto_nome
             FROM item_pedido i
             JOIN produto pr ON pr.id_produto = i.id_produto
             WHERE i.id_pedido = :id'
        );
        $stmt->execute(['id' => $id]);
        $pedido['itens'] = $stmt->fetchAll();

        $stmt = $this->db->prepare(
            'SELECT * FROM historico_status
             WHERE id_pedido = :id ORDER BY id_historico'
        );
        $stmt->execute(['id' => $id]);
        $pedido['historico'] = $stmt->fetchAll();

        return $pedido;
    }

    /**
     * Cria pedido + itens, baixa o estoque e registra o histórico.
     * @param array $itens  
     * @throws RuntimeException se algum produto não tiver estoque.
     */
    public function criar(int $idCliente, ?int $idUsuario, array $itens): int
    {
        $this->db->beginTransaction();
        try {
            // 1) Valida e trava o estoque de cada produto.
            // FOR UPDATE: evita que dois pedidos simultâneos vendam
            // as mesmas últimas unidades (corrida de estoque).
            $total = 0.0;
            $busca = $this->db->prepare(
                'SELECT nome, estoque FROM produto WHERE id_produto = :id FOR UPDATE'
            );
            foreach ($itens as $item) {
                $busca->execute(['id' => $item['id_produto']]);
                $produto = $busca->fetch();
                if (!$produto) {
                    throw new RuntimeException("Produto #{$item['id_produto']} não encontrado.");
                }
                if ((int) $produto['estoque'] < (int) $item['quantidade']) {
                    throw new RuntimeException(
                        "Estoque insuficiente de \"{$produto['nome']}\" (disponível: {$produto['estoque']})."
                    );
                }
                $total += $item['quantidade'] * $item['valor_unitario'];
            }

            // 2) Cabeçalho do pedido.
            $stmt = $this->db->prepare(
                'INSERT INTO pedido (id_cliente, id_usuario, status, valor_total)
                 VALUES (:cliente, :usuario, \'aberto\', :total)'
            );
            $stmt->execute([
                'cliente' => $idCliente,
                'usuario' => $idUsuario,
                'total'   => $total,
            ]);
            $idPedido = (int) $this->db->lastInsertId();

            // 3) Itens + baixa de estoque.
            $insItem = $this->db->prepare(
                'INSERT INTO item_pedido (id_pedido, id_produto, quantidade, valor_unitario)
                 VALUES (:pedido, :produto, :qtd, :valor)'
            );
            $baixa = $this->db->prepare(
                'UPDATE produto SET estoque = estoque - :qtd WHERE id_produto = :id'
            );
            foreach ($itens as $item) {
                $insItem->execute([
                    'pedido'  => $idPedido,
                    'produto' => $item['id_produto'],
                    'qtd'     => $item['quantidade'],
                    'valor'   => $item['valor_unitario'],
                ]);
                $baixa->execute([
                    'qtd' => $item['quantidade'],
                    'id'  => $item['id_produto'],
                ]);
            }

            // 4) Histórico: nascimento do pedido (— → aberto).
            $this->registrarHistorico($idPedido, null, 'aberto');

            $this->db->commit();
            return $idPedido;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // Muda o status e registra a transição no histórico
    public function atualizarStatus(int $id, string $novoStatus): void
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT status FROM pedido WHERE id_pedido = :id FOR UPDATE');
            $stmt->execute(['id' => $id]);
            $atual = $stmt->fetchColumn();

            if ($atual !== $novoStatus) {
                $upd = $this->db->prepare('UPDATE pedido SET status = :s WHERE id_pedido = :id');
                $upd->execute(['s' => $novoStatus, 'id' => $id]);
                $this->registrarHistorico($id, $atual, $novoStatus);
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    
     // Exclui o pedido devolvendo o estoque dos itens.
     // apagá-lo sem estornar deixaria o estoque menor do que o real.
     // itens e histórico caem via ON DELETE CASCADE.
     
    public function excluir(int $id): void
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'SELECT id_produto, quantidade FROM item_pedido WHERE id_pedido = :id'
            );
            $stmt->execute(['id' => $id]);
            $devolve = $this->db->prepare(
                'UPDATE produto SET estoque = estoque + :qtd WHERE id_produto = :id'
            );
            foreach ($stmt->fetchAll() as $item) {
                $devolve->execute(['qtd' => $item['quantidade'], 'id' => $item['id_produto']]);
            }

            $del = $this->db->prepare('DELETE FROM pedido WHERE id_pedido = :id');
            $del->execute(['id' => $id]);

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function registrarHistorico(int $idPedido, ?string $de, string $para): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO historico_status (id_pedido, status_anterior, status_novo)
             VALUES (:pedido, :de, :para)'
        );
        $stmt->execute(['pedido' => $idPedido, 'de' => $de, 'para' => $para]);
    }

    public function contar(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM pedido')->fetchColumn();
    }
}
