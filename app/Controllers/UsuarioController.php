<?php

namespace App\Controllers;

use App\Core\Request;
use App\Helpers\Response;
use App\Helpers\Validator;
use App\Models\Usuario;
use PDOException;

/**
 * CRUD de usuários — restrito a admin (ver routes/api.php).
 *
 * Todas as rotas usam ['auth','admin']. Além disso, o controller
 * aplica 3 regras de segurança que a simples checagem de admin
 * não cobre:
 *
 *  1. Não excluir o ÚLTIMO admin (senão ninguém mais administra).
 *  2. Não excluir a PRÓPRIA conta (evita o admin se auto-remover
 *     e perder acesso no meio de uma sessão).
 *  3. Um admin não pode REBAIXAR a si mesmo se for o último admin
 *     (mesmo motivo do item 1, por outro caminho).
 */
class UsuarioController
{
    private Usuario $model;

    public function __construct()
    {
        $this->model = new Usuario();
    }

    // GET /api/usuarios
    public function index(Request $request): void
    {
        Response::success($this->model->listar());
    }

    // GET /api/usuarios/{id}
    public function show(Request $request): void
    {
        $usuario = $this->model->buscar((int) $request->params['id']);
        if (!$usuario) {
            Response::error('Usuário não encontrado', 404);
        }
        Response::success($usuario);
    }

    // POST /api/usuarios  (criar — alternativa ao /api/register)
    public function store(Request $request): void
    {
        $dados = $request->body();
        $v = (new Validator($dados))
            ->obrigatorio('nome_usuario', 'nome')
            ->obrigatorio('email', 'e-mail')
            ->email('email')
            ->obrigatorio('senha', 'senha');

        if (!$v->passou()) {
            Response::error('Dados inválidos', 422, $v->erros());
        }
        if (strlen($dados['senha']) < 6) {
            Response::error('A senha deve ter ao menos 6 caracteres', 422);
        }

        // tipo só pode ser 'comum' ou 'admin' — nunca um valor arbitrário.
        $dados['tipo_usuario'] = ($dados['tipo_usuario'] ?? 'comum') === 'admin' ? 'admin' : 'comum';

        if ($this->model->buscarPorEmail($dados['email'])) {
            Response::error('E-mail já cadastrado', 409);
        }

        try {
            $id = $this->model->criar($dados);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                Response::error('E-mail já cadastrado', 409);
            }
            throw $e;
        }

        Response::created(['id_usuario' => $id], "/api/usuarios/{$id}", 'Usuário criado');
    }

    // PUT /api/usuarios/{id}
    public function update(Request $request): void
    {
        $id = (int) $request->params['id'];
        $alvo = $this->model->buscar($id);
        if (!$alvo) {
            Response::error('Usuário não encontrado', 404);
        }

        $dados = $request->body();
        $v = (new Validator($dados))
            ->obrigatorio('nome_usuario', 'nome')
            ->obrigatorio('email', 'e-mail')
            ->email('email');

        if (!$v->passou()) {
            Response::error('Dados inválidos', 422, $v->erros());
        }
        if (!empty($dados['senha']) && strlen($dados['senha']) < 6) {
            Response::error('A senha deve ter ao menos 6 caracteres', 422);
        }

        $dados['tipo_usuario'] = ($dados['tipo_usuario'] ?? $alvo['tipo_usuario']) === 'admin' ? 'admin' : 'comum';

        // Regra 3: impedir que o último admin se rebaixe a comum.
        $euMesmo = (int) ($request->params['_auth']['id'] ?? 0) === $id;
        $rebaixandoAdmin = $alvo['tipo_usuario'] === 'admin' && $dados['tipo_usuario'] === 'comum';
        if ($euMesmo && $rebaixandoAdmin && $this->model->contarAdmins() <= 1) {
            Response::error('Você é o único administrador — não é possível rebaixar sua conta.', 409);
        }

        // e-mail único: se mudou para um que já existe em OUTRO usuário
        $porEmail = $this->model->buscarPorEmail($dados['email']);
        if ($porEmail && (int) $porEmail['id_usuario'] !== $id) {
            Response::error('E-mail já cadastrado para outro usuário', 409);
        }

        $this->model->atualizar($id, $dados);
        Response::success(null, 'Usuário atualizado');
    }

    // DELETE /api/usuarios/{id}
    public function destroy(Request $request): void
    {
        $id = (int) $request->params['id'];
        $alvo = $this->model->buscar($id);
        if (!$alvo) {
            Response::error('Usuário não encontrado', 404);
        }

        $meuId = (int) ($request->params['_auth']['id'] ?? 0);

        // Regra 2: não excluir a própria conta.
        if ($meuId === $id) {
            Response::error('Você não pode excluir a própria conta.', 409);
        }

        // Regra 1: não excluir o último admin do sistema.
        if ($alvo['tipo_usuario'] === 'admin' && $this->model->contarAdmins() <= 1) {
            Response::error('Não é possível excluir o único administrador do sistema.', 409);
        }

        try {
            $this->model->excluir($id);
        } catch (PDOException $e) {
            // Usuário com pedidos vinculados (id_usuario em pedido).
            if ($e->getCode() === '23000') {
                Response::error('Este usuário possui registros vinculados e não pode ser excluído.', 409);
            }
            throw $e;
        }

        Response::noContent();
    }
}
