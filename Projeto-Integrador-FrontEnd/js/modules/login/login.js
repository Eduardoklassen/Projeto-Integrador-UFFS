/* 
   LOGIN.JS — Controlador da tela de login

  Liga o formulário à lógica de autenticação (Auth).
  Decisões de design (ver justificativas no chat):
  Coleta de dados com FormData 
  addEventListener + preventDefault 

*/

const DESTINO_POS_LOGIN = 'dashboard.html';

document.addEventListener('DOMContentLoaded', () => {
  // Se já estiver logado, pula direto pro sistema.
  if (window.Auth.estaLogado()) {
    window.location.href = DESTINO_POS_LOGIN;
    return;
  }

  const form        = document.getElementById('form-login');
  const inputSenha  = document.getElementById('senha');
  const btnEntrar   = document.getElementById('btn-entrar');
  const alerta      = document.getElementById('alerta');
  const toggleSenha = document.getElementById('toggle-senha');
  const linkEsqueci = document.getElementById('link-esqueci');

  // Mostrar / ocultar senha 
  toggleSenha.addEventListener('click', () => {
    const tipo = inputSenha.type === 'password' ? 'text' : 'password';
    inputSenha.type = tipo;
    toggleSenha.textContent = tipo === 'password' ? 'Mostrar' : 'Ocultar';
  });

  
  // Usa textContent (não innerHTML): o conteúdo pode conter o nome
  // do usuário vindo da API —  */
  // Sessão expirada por inatividade (15 min): o http.js redireciona

  if (new URLSearchParams(window.location.search).get('sessao') === 'expirada') {
    mostrarAlerta('Sua sessão expirou após 15 minutos. Faça login novamente.', 'erro');
  }

  function mostrarAlerta(mensagem, tipo = 'erro') {
    alerta.textContent = mensagem;
    alerta.className = `login__alerta login__alerta--visivel login__alerta--${tipo}`;
  }

  function limparAlerta() {
    alerta.className = 'login__alerta';
    alerta.textContent = '';
  }

  // Submissão do formulário 
  form.addEventListener('submit', async (evento) => {
    evento.preventDefault();   // impede o reload padrão (handout 9.2)
    limparAlerta();

    // FormData coleta todos os campos de uma vez pelo atributo name
    const dados = Object.fromEntries(new FormData(form));
    // dados = { email: "...", senha: "..." }

    // Higiene de entrada: limpa o e-mail (trim + remove invisíveis).
    // A senha NÃO é alterada (espaços podem ser intencionais).
    dados.email = window.Validators.limpar(dados.email ?? '');

    // Validação no front antes de chamar a API.
    const erroEmail = window.Validators.combinar(dados.email ?? '', [
      (v) => window.Validators.obrigatorio(v, 'E-mail'),
      (v) => window.Validators.email(v),
    ]);
    const erroSenha = window.Validators.obrigatorio(dados.senha ?? '', 'Senha');

    if (erroEmail || erroSenha) {
      mostrarAlerta(erroEmail || erroSenha, 'erro');
      return;
    }

    // Estado de carregando.
    btnEntrar.disabled = true;
    btnEntrar.classList.add('btn--carregando');

    try {
      const usuario = await window.Auth.login(dados);
      mostrarAlerta(`Bem-vindo, ${usuario.nome}!`, 'sucesso');

      setTimeout(() => {
        window.location.href = DESTINO_POS_LOGIN;
      }, 600);

    } catch (erro) {
      mostrarAlerta(erro.mensagem || 'Falha ao realizar login.', 'erro');
      btnEntrar.disabled = false;
      btnEntrar.classList.remove('btn--carregando');
    }
  });

  // Esqueci a senha
  // Faltou implementar 
    
  linkEsqueci.addEventListener('click', (evento) => {
    evento.preventDefault();
    mostrarAlerta(
      'Para recuperar a senha, entre em contato com o administrador do sistema.',
      'erro'
    );
  });
});
