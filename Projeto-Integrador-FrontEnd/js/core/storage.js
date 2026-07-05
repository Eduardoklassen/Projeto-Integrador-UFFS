/* 
   STORAGE.JS — Gerência de armazenamento local

   Centraliza leitura/escrita do token JWT e dados do usuário
   no localStorage. Nenhum outro arquivo deve acessar o
   localStorage diretamente
*/

const Storage = {
  // Chaves usadas no localStorage (prefixo evita conflito).
  CHAVES: {
    TOKEN:   'pi_token',
    USUARIO: 'pi_usuario',
  },

  // Token JWT 
  salvarToken(token) {
    localStorage.setItem(this.CHAVES.TOKEN, token);
  },

  obterToken() {
    return localStorage.getItem(this.CHAVES.TOKEN);
  },

  removerToken() {
    localStorage.removeItem(this.CHAVES.TOKEN);
  },

  // Dados do usuário 
  salvarUsuario(usuario) {
    localStorage.setItem(this.CHAVES.USUARIO, JSON.stringify(usuario));
  },

  obterUsuario() {
    const dados = localStorage.getItem(this.CHAVES.USUARIO);
    if (!dados) return null;
    // Protege contra dado corrompido/adulterado no localStorage:
    // se o JSON for inválido, limpa e retorna null em vez de quebrar.
    try {
      return JSON.parse(dados);
    } catch (erro) {
      this.removerUsuario();
      return null;
    }
  },

  removerUsuario() {
    localStorage.removeItem(this.CHAVES.USUARIO);
  },

  // Limpeza geral(logout)
  limparTudo() {
    this.removerToken();
    this.removerUsuario();
  },
};

window.Storage = Storage;
