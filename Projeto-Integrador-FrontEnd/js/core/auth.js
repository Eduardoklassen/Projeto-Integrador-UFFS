  // AUTH.JS — Serviço de Autenticação 
  // Lógica de login/logout e verificação de sessão. Usa o Http
  // para falar com a API e o Storage para guardar o token.

const Auth = {
  /**
   * //Realiza o login na API.
   * @param {object} credenciais - campos esperados pelo backend
   * @returns {object} usuário autenticado
   */
  async login(credenciais) {
    const resposta = await window.Http.post(
      window.API_CONFIG.ENDPOINTS.LOGIN,
      credenciais,
      { autenticar: false }   // login não exige token prévio
    );

    // Resposta esperada do backend:
    const { token, usuario } = resposta.dados;

    window.Storage.salvarToken(token);
    window.Storage.salvarUsuario(usuario);

    return usuario;
  },

  // Encerra a sessão local
  logout() {
    window.Storage.limparTudo();
  },

  // Retorna true se houver token salvo
  estaLogado() {
    return !!window.Storage.obterToken();
  },

  // Retorna os dados do usuário logado (ou null)
  usuarioAtual() {
    return window.Storage.obterUsuario();
  },

  
  // Protege uma página: se não estiver logado, redireciona
  // para a tela de login. Chamar no topo das páginas internas.
   
  exigirLogin(paginaLogin = 'index.html') {
    if (!this.estaLogado()) {
      window.location.href = paginaLogin;
      return;
    }
    // Sessão válida: liga o logout automático por inatividade
    // (15 min sem atividade e volta ao login). Só ativa se o
    // módulo estiver carregado na página.
    if (window.Inatividade) {
      window.Inatividade.iniciar(paginaLogin);
    }
  },
};

window.Auth = Auth;
