/* 
   DASHBOARD.JS — Controlador da página Dashboard (INTEGRADO)

   4 contadores reais de GET /api/dashboard:
   total_produtos, total_pedidos, total_clientes, total_fornecedores.
*/

document.addEventListener('DOMContentLoaded', () => {
  window.Auth.exigirLogin('index.html');
  window.Layout.montar({ ativo: 'dashboard', titulo: 'Dashboard' });
  carregarDashboard();
});

async function carregarDashboard() {
  const cont = document.getElementById('kpis');
  cont.textContent = 'Carregando...';

  try {
    const resposta = await window.Http.get(window.API_CONFIG.ENDPOINTS.DASHBOARD);
    const d = resposta.dados || {};

    const kpis = [
      { rotulo: 'Produtos cadastrados',  valor: d.total_produtos ?? 0,     cor: 'vinho',   ico: '▤', href: 'produtos.html' },
      { rotulo: 'Pedidos registrados',   valor: d.total_pedidos ?? 0,      cor: 'verde',   ico: '↑', href: 'pedidos.html' },
      { rotulo: 'Clientes cadastrados',  valor: d.total_clientes ?? 0,     cor: 'azul',    ico: '☺', href: 'clientes.html' },
      { rotulo: 'Fornecedores',          valor: d.total_fornecedores ?? 0, cor: 'laranja', ico: '▢', href: 'fornecedores.html' },
    ];
    renderKpis(kpis);
  } catch (erro) {
    cont.textContent = '';
    const aviso = window.Dom.criarComTexto('p', '', erro.mensagem || 'Erro ao carregar o painel.');
    aviso.style.color = 'var(--cor-erro)';
    cont.appendChild(aviso);
  }
}

function renderKpis(kpis) {
  const cont = document.getElementById('kpis');
  window.Dom.limpar(cont);
  kpis.forEach((k) => {
    // Card clicável que leva à tela correspondente
    const card = window.Dom.criar('a', 'kpi');
    card.href = k.href;
    card.style.textDecoration = 'none';
    card.style.color = 'inherit';

    const ico = window.Dom.criarComTexto('div', `kpi__ico kpi__ico--${k.cor}`, k.ico);
    const info = window.Dom.criar('div');
    info.appendChild(window.Dom.criarComTexto('div', 'kpi__rotulo', k.rotulo));
    info.appendChild(window.Dom.criarComTexto('div', 'kpi__valor', String(k.valor)));
    card.append(ico, info);
    cont.appendChild(card);
  });
}
