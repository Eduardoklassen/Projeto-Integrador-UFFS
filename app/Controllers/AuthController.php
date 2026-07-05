<?php

namespace App\Controllers;

use App\Core\Request;
use App\Helpers\Response;
use App\Helpers\RateLimiter;
use App\Models\Usuario;
use App\Services\AuthService;

/**
 * Autenticação — versão endurecida (hardened).
 *
 * Correções de segurança aplicadas:
 *  - Brute-force: rate limit por IP no login (VULN #2).
 *  - Escalonamento de privilégio: register nunca aceita
 *    'tipo_usuario' vindo do cliente (VULN #1).
 *  - Enumeração de usuário: mensagens e tempo uniformes (VULN #4).
 */
class AuthController
{
    // Após 5 falhas em 15 min a partir do mesmo IP, bloqueia por 15 min.
    private const MAX_TENTATIVAS = 5;
    private const JANELA_SEGUNDOS = 900;

    public function login(Request $request): void
    {
        $ip = $request->ip();
        $limiter = new RateLimiter();

        // VULN #2 — Brute-force de senha.
        // COMO O ATACANTE FARIA: script tentando milhares de senhas
        // no /api/login. Sem limite, é só questão de tempo.
        // DEFESA: trava o IP após N falhas e responde 429.
        if ($limiter->bloqueado("login:{$ip}", self::MAX_TENTATIVAS, self::JANELA_SEGUNDOS)) {
            Response::error(
                'Muitas tentativas de login. Aguarde alguns minutos e tente novamente.',
                429
            );
        }

        $email = $request->input('email');
        $senha = $request->input('senha');

        if (!$email || !$senha) {
            Response::error('E-mail e senha são obrigatórios', 422);
        }

        $usuario = (new Usuario())->buscarPorEmail($email);

        // VULN #4 — Enumeração de usuários.
        // COMO O ATACANTE FARIA: comparar a resposta de um e-mail que
        // existe vs. um que não existe para montar lista de e-mails
        // válidos. DEFESA: password_verify SEMPRE roda (mesmo sem
        // usuário, contra um hash-isca) para o tempo de resposta não
        // denunciar a existência; e a mensagem é sempre a mesma.
        $hashIsca = '$2y$10$usuarioinexistente0000000000000000000000000000000000000';
        $hashParaVerificar = $usuario['senha_hash'] ?? $hashIsca;
        $senhaConfere = password_verify($senha, $hashParaVerificar);

        if (!$usuario || !$senhaConfere) {
            $limiter->registrarFalha("login:{$ip}", self::JANELA_SEGUNDOS);
            // Log interno detalhado, resposta genérica ao cliente (VULN #6).
            error_log("[LOGIN FALHOU] ip={$ip} email={$email} existe=" . ($usuario ? 'sim' : 'nao'));
            Response::error('Credenciais inválidas', 401);
        }

        // Sucesso: zera o contador de falhas daquele IP.
        $limiter->limpar("login:{$ip}");

        $token = (new AuthService())->gerarToken($usuario);

        Response::success([
            'token'   => $token,
            'usuario' => [
                'id'   => $usuario['id_usuario'],
                'nome' => $usuario['nome_usuario'],
                'tipo' => $usuario['tipo_usuario'],
            ],
        ], 'Login realizado com sucesso');
    }

    /**
     * Cadastro de usuário.
     *
     * Esta rota deve ser protegida por ['auth','admin'] no routes/api.php
     * — só um admin logado cria novos usuários. Ainda assim, o controller
     * se protege sozinho quanto ao tipo (defesa em profundidade).
     */
    public function register(Request $request): void
    {
        $dados = $request->body();

        if (empty($dados['email']) || empty($dados['senha']) || empty($dados['nome_usuario'])) {
            Response::error('Nome, e-mail e senha são obrigatórios', 422);
        }

        // Higiene de senha: comprimento mínimo (evita "123").
        if (strlen($dados['senha']) < 6) {
            Response::error('A senha deve ter ao menos 6 caracteres', 422);
        }

        // VULN #1 — Escalonamento de privilégio (a mais grave).
        // COMO O ATACANTE FARIA: POST /api/register com
        // {"nome_usuario":"x","email":"x@x","senha":"x","tipo_usuario":"admin"}
        // e viraria admin. DEFESA: o tipo NUNCA vem do cliente. Quem
        // define é o servidor. Novo usuário nasce 'comum'; só um admin
        // já autenticado pode criar outro admin, de forma explícita.
        $solicitanteEhAdmin = ($request->params['_auth']['tipo'] ?? null) === 'admin';
        $tipoDesejado = $dados['tipo_usuario'] ?? 'comum';
        $dados['tipo_usuario'] =
            ($solicitanteEhAdmin && $tipoDesejado === 'admin') ? 'admin' : 'comum';

        $model = new Usuario();
        if ($model->buscarPorEmail($dados['email'])) {
            Response::error('E-mail já cadastrado', 409);
        }

        $id = $model->criar($dados);
        Response::success(['id_usuario' => $id], 'Usuário criado com sucesso', 201);
    }
}
