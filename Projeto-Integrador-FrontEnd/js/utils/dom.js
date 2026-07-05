/* 
  DOM.JS — Helpers de manipulação do DOM (compartilhados)
  
  Funções utilitárias para criar elementos com segurança
  Usadas por todos os módulos de tela (dashboard, produtos)

  Todos os helpers usam textContent / createElement — nunca
  innerHTML com dado. Assim, qualquer dado externo (vindo da
  API) é tratado como texto.
*/

const Dom = {
  criar(tag, classe) {
    const el = document.createElement(tag);
    if (classe) el.className = classe;
    return el;
  },

  criarComTexto(tag, classe, texto) {
    const el = this.criar(tag, classe);
    el.textContent = texto;
    return el;
  },

  td(texto, classe = 'tabela__td') {
    return this.criarComTexto('td', classe, String(texto));
  },

  tdBadge(texto, variacao, classeTd = 'tabela__td') {
    const cel = this.criar('td', classeTd);
    const badge = this.criarComTexto('span', `badge ${variacao}`, texto);
    cel.appendChild(badge);
    return cel;
  },

  limpar(el) {
    if (el) el.textContent = '';
  },

  $(seletor, contexto = document) {
    return contexto.querySelector(seletor);
  },

  $$(seletor, contexto = document) {
    return Array.from(contexto.querySelectorAll(seletor));
  },
};

window.Dom = Dom;
