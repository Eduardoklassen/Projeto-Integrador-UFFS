/* 
   MODAL.JS — Controle de janela de detalhe (compartilhado)

   Cria/abre/fecha um modal. O conteúdo é montado pelo chamador
   usando elementos do Dom, mantendo
   a segurança anti-XSS.
*/

const Modal = {
  _el: null,

  // Garante que a estrutura do modal existe no DOM (cria uma vez)
  _garantir() {
    if (this._el) return;
    const overlay = window.Dom.criar('div', 'modal');
    overlay.id = 'modal-global';
    const caixa = window.Dom.criar('div', 'modal__caixa');

    const cab = window.Dom.criar('div', 'modal__cabecalho');
    const titulo = window.Dom.criar('h2', 'modal__titulo');
    titulo.id = 'modal-titulo';
    const fechar = window.Dom.criarComTexto('button', 'modal__fechar', '✕');
    fechar.setAttribute('aria-label', 'Fechar');
    fechar.addEventListener('click', () => this.fechar());
    cab.append(titulo, fechar);

    const corpo = window.Dom.criar('div', 'modal__corpo');
    corpo.id = 'modal-corpo';

    caixa.append(cab, corpo);
    overlay.appendChild(caixa);

    // Fecha ao clicar fora da caixa.
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) this.fechar();
    });
    // Fecha com ESC.
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') this.fechar();
    });

    document.body.appendChild(overlay);
    this._el = overlay;
  },

  /**
   * Abre o modal.
   * @param {string} titulo
   * @param {Node|Node[]} conteudo - elementos já criados (seguros)
   */
  abrir(titulo, conteudo) {
    this._garantir();
    document.getElementById('modal-titulo').textContent = titulo;
    const corpo = document.getElementById('modal-corpo');
    window.Dom.limpar(corpo);
    if (Array.isArray(conteudo)) corpo.append(...conteudo);
    else if (conteudo) corpo.appendChild(conteudo);
    this._el.classList.add('modal--aberto');
  },

  fechar() {
    if (this._el) this._el.classList.remove('modal--aberto');
  },
};

window.Modal = Modal;
