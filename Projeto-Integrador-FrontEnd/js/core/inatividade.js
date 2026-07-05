/* 
   INATIVIDADE.JS — Logout automático por inatividade
  
   (além do 401 do back): o token expira em 15 min
   no servidor, mas o front só descobriria isso na próxima
   requisição. Este timer torna o logout VISÍVEL e imediato — a
   tela volta sozinha para o login no minuto 15, mesmo que o
   usuário não clique em nada. Requisito de segurança do projeto.

   um temporizador é iniciado a cada sinal de
   atividade. Se ele chegar ao fim sem ser reiniciado, desloga.
   O tempo é alinhado ao JWT_EXPIRES do back (15 min) para os
   dois vencerem juntos.
*/

const Inatividade = {
  // 15 minutos em milissegundos — igual ao JWT_EXPIRES=900s do back.
  LIMITE_MS: 15 * 60 * 1000,

  _timer: null,
  _paginaLogin: 'index.html',

  
    // Inicia o monitoramento. Chamado automaticamente pelo
    // Auth.exigirLogin, então vale em todas as telas internas.
   
  iniciar(paginaLogin = 'index.html') {
    this._paginaLogin = paginaLogin;

    // Eventos que contam como "atividade". passive: true não
    // atrapalha a rolagem; cada um apenas reinicia o cronômetro.
    const eventos = ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll', 'click'];
    eventos.forEach((ev) =>
      window.addEventListener(ev, () => this._reiniciar(), { passive: true })
    );

    // Se o usuário volta para a aba depois de um tempo em outra,
    // reavaliamos na hora (visibilitychange).
    document.addEventListener('visibilitychange', () => {
      if (!document.hidden) this._reiniciar();
    });

    this._reiniciar();
  },

  // inicia a contagem regressiva de 15 min
  _reiniciar() {
    if (this._timer) clearTimeout(this._timer);
    this._timer = setTimeout(() => this._expirar(), this.LIMITE_MS);
  },

  // Encerra a sessão e vai para o login com o aviso
  _expirar() {
    window.Storage.limparTudo();
    // Mesmo parâmetro que o http.js usa no 401 — a tela de login
    // já sabe exibir "Sua sessão expirou após 15 minutos".
    window.location.href = this._paginaLogin + '?sessao=expirada';
  },
};

window.Inatividade = Inatividade;
