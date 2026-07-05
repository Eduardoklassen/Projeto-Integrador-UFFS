/* 
   LAYOUT.JS — Monta a moldura compartilhada (sidebar + topbar)

   Injeta a navegação em todas as telas internas a partir de um único lugar
   O HTML da moldura é ESTÁTICO e controlado por nós → innerHTML
   com string fixa é seguro (não há dado externo aqui).
   O ÚNICO dado dinâmico (nome do usuário, vindo do Storage) é
   inserido com textContent, nunca interpolado no HTML.
*/

const Layout = {
  // Definição do menu num único lugar. Adicionar tela = uma linha aqui.
  MENU_PRINCIPAL: [
    { id: 'dashboard',    rotulo: 'Dashboard',    ico: '▦', href: 'dashboard.html' },
    { id: 'produtos',     rotulo: 'Produtos',     ico: '▤', href: 'produtos.html' },
    { id: 'materiais',    rotulo: 'Materiais',    ico: '▥', href: 'materiais.html' },
    { id: 'pedidos',      rotulo: 'Pedidos',      ico: '↑', href: 'pedidos.html' },
    { id: 'compras',      rotulo: 'Compras',      ico: '↓', href: 'compras.html' },
    { id: 'clientes',     rotulo: 'Clientes',     ico: '☺', href: 'clientes.html' },
    { id: 'fornecedores', rotulo: 'Fornecedores', ico: '▢', href: 'fornecedores.html' },
    { id: 'financeiro',   rotulo: 'Financeiro',   ico: 'R$', href: 'financeiro.html' },
    { id: 'analise',      rotulo: 'Análise',      ico: '▰', href: 'analise.html' },
  ],
  MENU_CONFIG: [
    { id: 'usuarios', rotulo: 'Usuários', ico: '⚇', href: 'usuarios.html' },
  ],

  /**
   * Monta sidebar + topbar.
   * @param {object} opcoes
   *   - ativo: id do item de menu ativo (ex.: 'dashboard')
   *   - titulo: título exibido na topbar
   */
  montar({ ativo = '', titulo = '' } = {}) {
    const sidebarEl = document.querySelector('.app__sidebar');
    const topbarEl  = document.querySelector('.app__topbar');
    if (!sidebarEl || !topbarEl) return;

    // ---- Monta os links do menu (HTML estático e controlado) ----
    const linksPrincipal = this.MENU_PRINCIPAL.map((item) =>
      this._linkHtml(item, ativo)
    ).join('');
    const linksConfig = this.MENU_CONFIG.map((item) =>
      this._linkHtml(item, ativo)
    ).join('');

    sidebarEl.classList.add('sidebar');
    sidebarEl.innerHTML = `
      <div class="sidebar__logo">
        <div class="sidebar__logo-icone">NK</div>
        <span class="sidebar__logo-texto">Nino<span>Klassen</span></span>
      </div>
      <div class="sidebar__grupo">MENU PRINCIPAL</div>
      <nav class="sidebar__nav" aria-label="Navegação principal">${linksPrincipal}</nav>
      <div class="sidebar__grupo">CONFIGURAÇÕES</div>
      <nav class="sidebar__nav" aria-label="Configurações">${linksConfig}</nav>
      <div class="sidebar__rodape">
        <div class="sidebar__avatar" id="layout-avatar">A</div>
        <div>
          <div class="sidebar__usuario-nome" id="layout-nome">Usuário</div>
          <div class="sidebar__usuario-papel" id="layout-papel">—</div>
        </div>
      </div>
    `;

    topbarEl.classList.add('topbar');
    topbarEl.innerHTML = `
      <div class="topbar__esquerda">
        <button class="topbar__menu-btn" id="btn-menu" aria-label="Abrir menu" aria-expanded="false">☰</button>
        <span class="topbar__titulo" id="layout-titulo"></span>
      </div>
      <div class="topbar__acoes" id="topbar-acoes"></div>
    `;

    // Overlay que escurece o fundo quando o menu mobile está aberto.
    if (!document.getElementById('sidebar-overlay')) {
      const ov = document.createElement('div');
      ov.id = 'sidebar-overlay';
      ov.className = 'sidebar-overlay';
      document.body.appendChild(ov);
    }

    // Preenche dados DINÂMICOS com textContent 
    const usuario = window.Auth ? window.Auth.usuarioAtual() : null;
    const nome = usuario && usuario.nome ? usuario.nome : 'Usuário';
    const papel = usuario && usuario.tipo ? usuario.tipo : '—';

    document.getElementById('layout-nome').textContent = nome;
    document.getElementById('layout-papel').textContent = papel;
    document.getElementById('layout-avatar').textContent = nome.charAt(0).toUpperCase();
    document.getElementById('layout-titulo').textContent = titulo;

    this._ligarMenuMobile(sidebarEl);
  },

  _ligarMenuMobile(sidebarEl) {
    const btn = document.getElementById('btn-menu');
    const overlay = document.getElementById('sidebar-overlay');
    if (!btn || !overlay) return;

    const abrir = () => {
      sidebarEl.classList.add('sidebar--aberta');
      overlay.classList.add('sidebar-overlay--visivel');
      btn.setAttribute('aria-expanded', 'true');
    };
    const fechar = () => {
      sidebarEl.classList.remove('sidebar--aberta');
      overlay.classList.remove('sidebar-overlay--visivel');
      btn.setAttribute('aria-expanded', 'false');
    };
    const alternar = () => sidebarEl.classList.contains('sidebar--aberta') ? fechar() : abrir();

    btn.addEventListener('click', alternar);
    overlay.addEventListener('click', fechar);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') fechar(); });
    // Ao tocar num link do menu, fecha (navegação em nova página).
    sidebarEl.querySelectorAll('.sidebar__link').forEach((l) =>
      l.addEventListener('click', fechar)
    );
  },

  // Gera o HTML de um link de menu (conteúdo estático controlado)
  _linkHtml(item, ativo) {
    const classeAtivo = item.id === ativo ? ' sidebar__link--ativo' : '';
    return `
      <a class="sidebar__link${classeAtivo}" href="${item.href}">
        <span class="sidebar__ico">${item.ico}</span> ${item.rotulo}
      </a>`;
  },
};

window.Layout = Layout;
