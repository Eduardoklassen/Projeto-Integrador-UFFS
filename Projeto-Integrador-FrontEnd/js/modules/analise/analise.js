/* 
  ANALISE.JS — Análise de Estoque com DADOS REAIS
  
  TODOS os números vêm da API.
  DECISÃO DE ARQUITETURA — por que NÃO criamos /api/analise:
  os dados necessários já existem nas rotas atuais (pedidos,
  compras, produtos, receitas, despesas). 
  1- zero mudança no back = menos risco;
  2- menos código para manter;

 */

const VINHO = '#9e1b1b', VERDE = '#16a34a', AZUL = '#2563eb',
      LARANJA = '#d97706', GRAFITE = '#2d2d2d', CINZA = '#cbd5e1',
      ROXO = '#7c3aed';

const LIMITE_ALERTA = 5; // estoque igual/abaixo disto entra no KPI de alerta

function moedaCompacta(n) {
  const v = Number(n) || 0;
  const abs = Math.abs(v);
  if (abs >= 1e9) return 'R$ ' + (v / 1e9).toLocaleString('pt-BR', { maximumFractionDigits: 1 }) + ' bi';
  if (abs >= 1e6) return 'R$ ' + (v / 1e6).toLocaleString('pt-BR', { maximumFractionDigits: 1 }) + ' mi';
  if (abs >= 1e3) return 'R$ ' + (v / 1e3).toLocaleString('pt-BR', { maximumFractionDigits: 1 }) + ' mil';
  return 'R$ ' + v.toLocaleString('pt-BR', { maximumFractionDigits: 2 });
}

function opcoesMoeda() {
  return {
    responsive: true, maintainAspectRatio: false,
    scales: { y: { ticks: { callback: (v) => moedaCompacta(v) } } },
    plugins: { tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label}: ${moedaCompacta(ctx.parsed.y ?? ctx.parsed)}` } } },
  };
}

let _graficos = {};
let _dados = null; // cache: { pedidos, compras, produtos, receitas, despesas }

document.addEventListener('DOMContentLoaded', () => {
  window.Auth.exigirLogin('index.html');
  window.Layout.montar({ ativo: 'analise', titulo: 'Análise de Estoque' });

  injetarFiltros();
  iniciar('30d');
});

async function iniciar(periodo) {
  try {
    await carregarDados();
  } catch (e) {
    const cont = document.getElementById('kpis');
    window.Dom.limpar(cont);
    const aviso = window.Dom.criarComTexto('p', '', e.mensagem || 'Erro ao carregar os dados da análise.');
    aviso.style.color = 'var(--cor-erro)';
    cont.appendChild(aviso);
    return;
  }

  atualizarTudo(periodo);
}

async function carregarDados() {
  if (_dados) return;
  const E = window.API_CONFIG.ENDPOINTS;
  const [pe, co, pr, re, de] = await Promise.all([
    window.Http.get(E.PEDIDOS),
    window.Http.get(E.COMPRAS),
    window.Http.get(E.PRODUTOS),
    window.Http.get(E.RECEITAS),
    window.Http.get(E.DESPESAS),
  ]);
  _dados = {
    pedidos:  Array.isArray(pe.dados) ? pe.dados : [],
    compras:  Array.isArray(co.dados) ? co.dados : [],
    produtos: Array.isArray(pr.dados) ? pr.dados : [],
    receitas: Array.isArray(re.dados) ? re.dados : [],
    despesas: Array.isArray(de.dados) ? de.dados : [],
  };
}

function atualizarTudo(periodo) {
  renderKpis(periodo);
  if (typeof Chart === 'undefined') {
    avisarChartIndisponivel();
    return;
  }
  montarGraficos(periodo);
}

function limitesDoPeriodo(periodo) {
  const fim = new Date();
  const inicio = new Date();
  if (periodo === '7d') inicio.setDate(fim.getDate() - 6);
  else if (periodo === '30d') inicio.setDate(fim.getDate() - 29);
  else inicio.setMonth(fim.getMonth() - 11, 1); // 12m: do 1º dia, 11 meses atrás
  inicio.setHours(0, 0, 0, 0);
  return { inicio, fim };
}

function noPeriodo(dataStr, inicio, fim) {
  if (!dataStr) return false;
  const d = new Date(String(dataStr).replace(' ', 'T'));
  return !isNaN(d) && d >= inicio && d <= fim;
}

function gerarBaldes(periodo) {
  const { inicio } = limitesDoPeriodo(periodo);
  const baldes = [];
  if (periodo === '12m') {
    const d = new Date(inicio);
    for (let i = 0; i < 12; i++) {
      baldes.push({ chave: `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`,
                    rotulo: d.toLocaleDateString('pt-BR', { month: 'short' }) });
      d.setMonth(d.getMonth() + 1);
    }
  } else {
    const dias = periodo === '7d' ? 7 : 30;
    const d = new Date(inicio);
    for (let i = 0; i < dias; i++) {
      baldes.push({ chave: d.toISOString().slice(0, 10),
                    rotulo: d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' }) });
      d.setDate(d.getDate() + 1);
    }
  }
  return baldes;
}


function somarPorBalde(registros, campoData, campoValor, periodo) {
  const baldes = gerarBaldes(periodo);
  const mapa = Object.fromEntries(baldes.map((b) => [b.chave, 0]));
  registros.forEach((r) => {
    const data = String(r[campoData] || '');
    const chave = periodo === '12m' ? data.slice(0, 7) : data.slice(0, 10);
    if (chave in mapa) mapa[chave] += Number(r[campoValor]) || 0;
  });
  return { rotulos: baldes.map((b) => b.rotulo), valores: baldes.map((b) => mapa[b.chave]) };
}


function renderKpis(periodo) {
  const { inicio, fim } = limitesDoPeriodo(periodo);
  // KPIs usam moeda compacta: o card é estreito e um valor por
  
  const moeda = (n) => moedaCompacta(n);

  const entradas = _dados.compras
    .filter((c) => noPeriodo(c.data_compra, inicio, fim))
    .reduce((s, c) => s + (Number(c.valor_total) || 0), 0);

  const saidas = _dados.pedidos
    .filter((p) => p.status !== 'cancelado' && noPeriodo(p.data_pedido, inicio, fim))
    .reduce((s, p) => s + (Number(p.valor_total) || 0), 0);

  const valorEstoque = _dados.produtos
    .filter((p) => Number(p.ativo) === 1)
    .reduce((s, p) => s + (Number(p.preco_final) || 0) * (Number(p.estoque) || 0), 0);

  const alerta = _dados.produtos
    .filter((p) => Number(p.ativo) === 1 && Number(p.estoque) <= LIMITE_ALERTA).length;

  const kpis = [
    { rotulo: 'Entradas (compras)', valor: moeda(entradas),     cor: 'verde',   ico: '↓' },
    { rotulo: 'Saídas (vendas)',    valor: moeda(saidas),       cor: 'vinho',   ico: '↑' },
    { rotulo: 'Valor em estoque',   valor: moeda(valorEstoque), cor: 'azul',    ico: 'R$' },
    { rotulo: `Itens em alerta (≤${LIMITE_ALERTA})`, valor: String(alerta), cor: 'laranja', ico: '⚠' },
  ];

  const cont = document.getElementById('kpis');
  window.Dom.limpar(cont);
  kpis.forEach((k) => {
    const card = window.Dom.criar('div', 'kpi');
    const ico = window.Dom.criarComTexto('div', `kpi__ico kpi__ico--${k.cor}`, k.ico);
    const info = window.Dom.criar('div');
    info.appendChild(window.Dom.criarComTexto('div', 'kpi__rotulo', k.rotulo));
    info.appendChild(window.Dom.criarComTexto('div', 'kpi__valor', k.valor));
    card.append(ico, info);
    cont.appendChild(card);
  });
}

  // Gráficos (Chart.js)
  
function destruirGraficos() {
  Object.values(_graficos).forEach((g) => g?.destroy());
  _graficos = {};
}

function montarGraficos(periodo) {
  destruirGraficos();
  const { inicio, fim } = limitesDoPeriodo(periodo);

  const entradas = somarPorBalde(_dados.compras, 'data_compra', 'valor_total', periodo);
  const pedidosValidos = _dados.pedidos.filter((p) => p.status !== 'cancelado');
  const saidas = somarPorBalde(pedidosValidos, 'data_pedido', 'valor_total', periodo);

  _graficos.fluxo = new Chart(document.getElementById('graf-fluxo'), {
    type: 'line',
    data: {
      labels: entradas.rotulos,
      datasets: [
        { label: 'Entradas (compras R$)', data: entradas.valores, borderColor: VERDE,
          backgroundColor: 'rgba(22,163,74,0.10)', fill: true, tension: 0.35 },
        { label: 'Saídas (vendas R$)', data: saidas.valores, borderColor: VINHO,
          backgroundColor: 'rgba(158,27,27,0.10)', fill: true, tension: 0.35 },
      ],
    },
    options: opcoesMoeda(),
  });

  const top = [..._dados.produtos]
    .filter((p) => Number(p.ativo) === 1)
    .sort((a, b) => Number(b.estoque) - Number(a.estoque))
    .slice(0, 8);
  _graficos.categoria = new Chart(document.getElementById('graf-categoria'), {
    type: 'bar',
    data: {
      labels: top.map((p) => p.nome),
      datasets: [{ label: 'Estoque (un)', data: top.map((p) => Number(p.estoque)), backgroundColor: AZUL }],
    },
    options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y',
               plugins: { legend: { display: false } } },
  });

  const porStatus = {};
  _dados.pedidos.filter((p) => noPeriodo(p.data_pedido, inicio, fim))
    .forEach((p) => { porStatus[p.status] = (porStatus[p.status] || 0) + 1; });
  const CORES_STATUS = { aberto: AZUL, pago: VERDE, enviado: ROXO, entregue: GRAFITE, cancelado: VINHO };
  _graficos.top = new Chart(document.getElementById('graf-top'), {
    type: 'doughnut',
    data: {
      labels: Object.keys(porStatus),
      datasets: [{ data: Object.values(porStatus),
                   backgroundColor: Object.keys(porStatus).map((s) => CORES_STATUS[s] || CINZA) }],
    },
    options: { responsive: true, maintainAspectRatio: false },
  });

  const porForma = {};
  _dados.receitas.filter((r) => noPeriodo(r.criado_em, inicio, fim))
    .forEach((r) => {
      const forma = r.forma_pagamento || 'não informado';
      porForma[forma] = (porForma[forma] || 0) + (Number(r.valor) || 0);
    });
  _graficos.forma = new Chart(document.getElementById('graf-forma'), {
    type: 'doughnut',
    data: {
      labels: Object.keys(porForma),
      datasets: [{ data: Object.values(porForma),
                   backgroundColor: [VERDE, AZUL, LARANJA, ROXO, VINHO, CINZA] }],
    },
    options: { responsive: true, maintainAspectRatio: false },
  });

  const rec = somarPorBalde(_dados.receitas, 'criado_em', 'valor', periodo);
  const desp = somarPorBalde(_dados.despesas, 'criado_em', 'valor', periodo);
  _graficos.valor = new Chart(document.getElementById('graf-valor'), {
    type: 'bar',
    data: {
      labels: rec.rotulos,
      datasets: [
        { label: 'Receitas (R$)', data: rec.valores, backgroundColor: VERDE },
        { label: 'Despesas (R$)', data: desp.valores, backgroundColor: VINHO },
      ],
    },
    options: opcoesMoeda(),
  });
}

  //Filtros de período na topbar

function injetarFiltros() {
  const acoes = window.Dom.$('.topbar__acoes');
  if (!acoes) return;
  window.Dom.limpar(acoes);
  const grupo = window.Dom.criar('div', 'filtros-periodo');
  [['7d', '7 dias'], ['30d', '30 dias'], ['12m', '12 meses']].forEach(([val, txt], i) => {
    const btn = window.Dom.criarComTexto('button', 'filtro-btn' + (i === 1 ? ' filtro-btn--ativo' : ''), txt);
    btn.addEventListener('click', () => {
      window.Dom.$$('.filtro-btn').forEach((b) => b.classList.remove('filtro-btn--ativo'));
      btn.classList.add('filtro-btn--ativo');
      atualizarTudo(val);
    });
    grupo.appendChild(btn);
  });
  acoes.appendChild(grupo);
}

// Aviso amigável se o Chart.js não carregou
function avisarChartIndisponivel() {
  window.Dom.$$('.grafico-wrap').forEach((wrap) => {
    window.Dom.limpar(wrap);
    const aviso = window.Dom.criarComTexto('p', '',
      'Gráfico indisponível — o Chart.js (CDN) não carregou. Verifique a conexão.');
    aviso.style.color = 'var(--cor-texto-suave)';
    aviso.style.padding = '2rem 1rem';
    wrap.appendChild(aviso);
  });
}
