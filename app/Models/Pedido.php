<?php

namespace App\Models;
use App\Core\Database;
use PDO;

/**
 * Pedido = venda para um cliente, lançada por um usuário do sistema (#3).
 * Um pedido tem vários itens (item_pedido). Ao criar, usamos TRANSAÇÃO e
 * damos baixa no estoque do produto. Mudanças de status são gravadas na
 * tabela historico_status (#6).
 */
class Pedido
{
    private PDO $db;

    private const STATUS_VALIDOS = ['aberto', 'pago', 'enviado', 'entregue', 'cancelado'];

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function listar(): array
    {
        $sql = 'SELECT p.*, c.nome AS cliente_nome, u.nome_usuario AS usuario_nome
                FROM pedido p
                JOIN cliente c ON c.id_cliente = p.id_cliente
                JOIN usuario u ON u.id_usuario = p.id_usuario
                ORDER BY p.data_pedido DESC';
        return $this->db->query($sql)->fetchAll();
    }

    // Pedido com seus itens e o histórico de status. 
    public function buscar(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*, c.nome AS cliente_nome, u.nome_usuario AS usuario_nome
             FROM pedido p
             JOIN cliente c ON c.id_cliente = p.id_cliente
             JOIN usuario u ON u.id_usuario = p.id_usuario
             WHERE p.id_pedido = :id'
        );
        $stmt->execute(['id' => $id]);
        $pedido = $stmt->fetch();
        if (!$pedido) {
            return null;
        }

        $itens = $this->db->prepare(
            'SELECT ip.*, pr.nome AS produto_nome
             FROM item_pedido ip
             JOIN produto pr ON pr.id_produto = ip.id_produto
             WHERE ip.id_pedido = :id'
        );
        $itens->execute(['id' => $id]);
        $pedido['itens'] = $itens->fetchAll();

        $hist = $this->db->prepare(
            'SELECT * FROM historico_status WHERE id_pedido = :id ORDER BY data_mudanca ASC'
        );
        $hist->execute(['id' => $id]);
        $pedido['historico'] = $hist->fetchAll();

        return $pedido;
    }

    /**
     * Cria um pedido com itens:
     * $idUsuario (de quem está logado),
     * $Itens = [ ['id_produto','quantidade','valor_unitario']
     */
    public function criar(array $d, int $idUsuario): int
    {
        $itens = $d['itens'] ?? [];

        $this->db->beginTransaction();
        try {
            $total = 0.0;
            foreach ($itens as $it) {
                $total += (float) $it['quantidade'] * (float) $it['valor_unitario'];
            }

            $stmt = $this->db->prepare(
                'INSERT INTO pedido (id_cliente, id_usuario, status, data_pedido, data_entrega, valor_total)
                 VALUES (:cli, :usr, :status, :dped, :dent, :total)'
            );
            $status = $d['status'] ?? 'aberto';
            $stmt->execute([
                'cli'    => $d['id_cliente'],
                'usr'    => $idUsuario,
                'status' => $status,
                'dped'   => $d['data_pedido'] ?? date('Y-m-d'),
                'dent'   => $d['data_entrega'] ?? null,
                'total'  => $total,
            ]);
            $idPedido = (int) $this->db->lastInsertId();

            $stmtItem = $this->db->prepare(
                'INSERT INTO item_pedido (id_pedido, id_produto, quantidade, valor_unitario)
                 VALUES (:ped, :prod, :qtd, :valor)'
            );
            $stmtEstoque = $this->db->prepare(
                'UPDATE produto SET estoque = estoque - :qtd WHERE id_produto = :prod'
            );

            foreach ($itens as $it) {
                $stmtItem->execute([
                    'ped'   => $idPedido,
                    'prod'  => $it['id_produto'],
                    'qtd'   => $it['quantidade'],
                    'valor' => $it['valor_unitario'],
                ]);
                // saída de produto: baixa no estoque
                $stmtEstoque->execute([
                    'qtd'  => $it['quantidade'],
                    'prod' => $it['id_produto'],
                ]);
            }

            // registra o status inicial no histórico.
            $this->registrarHistorico($idPedido, null, $status);

            $this->db->commit();
            return $idPedido;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // Altera apenas o status do pedido e grava a mudança no histórico. 
    public function mudarStatus(int $id, string $novoStatus): bool
    {
        if (!in_array($novoStatus, self::STATUS_VALIDOS, true)) {
            return false;
        }

        $atual = $this->buscarSimples($id);
        if (!$atual) {
            return false;
        }
        $anterior = $atual['status'];

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'UPDATE pedido SET status = :status WHERE id_pedido = :id'
            );
            $stmt->execute(['status' => $novoStatus, 'id' => $id]);

            $this->registrarHistorico($id, $anterior, $novoStatus);

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function excluir(int $id): bool
    {
        // itens e histórico saem em cascata pelas FKs ON DELETE CASCADE
        $stmt = $this->db->prepare('DELETE FROM pedido WHERE id_pedido = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function contar(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM pedido')->fetchColumn();
    }

    // Busca enxuta sem itens/histórico, uso interno. 
    private function buscarSimples(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM pedido WHERE id_pedido = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    // Insere uma linha no histórico de status.
    private function registrarHistorico(int $idPedido, ?string $anterior, string $novo): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO historico_status (id_pedido, status_anterior, status_novo)
             VALUES (:ped, :ant, :novo)'
        );
        $stmt->execute([
            'ped'  => $idPedido,
            'ant'  => $anterior,
            'novo' => $novo,
        ]);
    }
}

?>