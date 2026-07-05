/* 
   FINANCEIRO.JS — Controlador (INTEGRADO)
   
   Modelo real: caixas + receitas + despesas.
   Caixas: id_caixa, descricao, saldo
   Abas: Caixas | Receitas | Despesas.
*/

let _abaAtiva = 'caixas';
let _caixas = []; // cache dos caixas p/ montar o select do formulário

document.addEventListener('DOMContentLoaded', () => {
  window.Auth.exigirLogin('index.html');
  window.Layout.montar({ ativo: 'financeiro', titulo: 'Financeiro' });

  ligarAbas();
  ligarBotaoNovo();
  carregarAba('caixas');
});

function ligarAbas() {
  window.Dom.$$('.aba[data-aba]').forEach((aba) => {
    aba.addEventListener('click', () => {
      window.Dom.$$('.aba[data-aba]').forEach((a) => a.classList.remove('aba--ativa'));
      aba.classList.add('aba--ativa');
      _abaAtiva = aba.dataset.aba;
      atualizarBotaoNovo();
      carregarAba(_abaAtiva);
    });
  });
}


function ligarBotaoNovo() {
  const btn = document.getElementById('btn-novo-fin');
  btn.addEventListener('click', () => {
    if (_abaAtiva === 'receitas') abrirFormReceita();
    else if (_abaAtiva === 'despesas') abrirFormDespesa();
    else abrirFormCaixa();
  });
  atualizarBotaoNovo();
}


function atualizarBotaoNovo() {
  const btn = document.getElementById('btn-novo-fin');
  btn.style.display = '';
  if (_abaAtiva === 'receitas') btn.textContent = '＋ Nova receita';
  else if (_abaAtiva === 'despesas') btn.textContent = '＋ Nova despesa';
  else btn.textContent = '＋ Novo caixa';
}

// Cadastro de caixa (POST /api/caixas — rota existe no back)
function abrirFormCaixa() {
  window.FormModal.abrir({
    titulo: 'Novo caixa',
    campos: [
      { nome: 'descricao', rotulo: 'Descrição', tipo: 'text', obrigatorio: true, placeholder: 'ex.: Caixa Loja' },
      { nome: 'saldo', rotulo: 'Saldo inicial (R$)', tipo: 'number', placeholder: 'ex.: 0.00' },
    ],
    aoSalvar: async (dados) => {
      if (dados.saldo === '' || dados.saldo === undefined) delete dados.saldo;
      await window.Http.post(window.API_CONFIG.ENDPOINTS.CAIXAS, dados);
      _caixas = []; // invalida o cache p/ os selects de receita/despesa
      carregarAba('caixas');
    },
  });
}

// Garante que temos a lista de caixas para o select 
async function garantirCaixas() {
  if (_caixas.length) return _caixas;
  const resposta = await window.Http.get(window.API_CONFIG.ENDPOINTS.CAIXAS);
  _caixas = Array.isArray(resposta.dados) ? resposta.dados : [];
  return _caixas;
}

async function abrirFormReceita() {
  let caixas;
  try { caixas = await garantirCaixas(); }
  catch (e) { alert(e.mensagem || 'Erro ao carregar os caixas.'); return; }
  if (!caixas.length) { alert('Nenhum caixa cadastrado no sistema — cadastre um caixa no backend antes de lançar receitas.'); return; }

  window.FormModal.abrir({
    titulo: 'Nova receita',
    campos: [
      { nome: 'id_caixa', rotulo: 'Caixa', tipo: 'select', obrigatorio: true,
        opcoes: caixas.map((c) => ({ valor: String(c.id_caixa), rotulo: `#${c.id_caixa} — ${c.descricao || 'Caixa'}` })) },
      { nome: 'valor', rotulo: 'Valor (R$)', tipo: 'number', obrigatorio: true, placeholder: 'ex.: 150.00' },
      { nome: 'forma_pagamento', rotulo: 'Forma de pagamento', tipo: 'select',
        opcoes: [
          { valor: 'dinheiro', rotulo: 'Dinheiro' }, { valor: 'pix', rotulo: 'PIX' },
          { valor: 'cartao', rotulo: 'Cartão' }, { valor: 'boleto', rotulo: 'Boleto' },
          { valor: 'outro', rotulo: 'Outro' },
        ] },
      { nome: 'observacao', rotulo: 'Observação', tipo: 'text' },
    ],
    aoSalvar: async (dados) => {
      dados.id_caixa = Number(dados.id_caixa); // FK vai como número
      await window.Http.post(window.API_CONFIG.ENDPOINTS.RECEITAS, dados);
      carregarAba('receitas');
    },
  });
}

async function abrirFormDespesa() {
  let caixas;
  try { caixas = await garantirCaixas(); }
  catch (e) { alert(e.mensagem || 'Erro ao carregar os caixas.'); return; }
  if (!caixas.length) { alert('Nenhum caixa cadastrado no sistema — cadastre um caixa no backend antes de lançar despesas.'); return; }

  window.FormModal.abrir({
    titulo: 'Nova despesa',
    campos: [
      { nome: 'id_caixa', rotulo: 'Caixa', tipo: 'select', obrigatorio: true,
        opcoes: caixas.map((c) => ({ valor: String(c.id_caixa), rotulo: `#${c.id_caixa} — ${c.descricao || 'Caixa'}` })) },
      { nome: 'valor', rotulo: 'Valor (R$)', tipo: 'number', obrigatorio: true, placeholder: 'ex.: 80.00' },
      { nome: 'tipo_movimentacao', rotulo: 'Tipo de movimentação', tipo: 'select',
        opcoes: [
          { valor: 'operacional', rotulo: 'Operacional' },
          { valor: 'compra_material', rotulo: 'Compra de material' },
          { valor: 'outro', rotulo: 'Outro' },
        ] },
      { nome: 'observacao', rotulo: 'Observação', tipo: 'text' },
    ],
    aoSalvar: async (dados) => {
      dados.id_caixa = Number(dados.id_caixa);
      await window.Http.post(window.API_CONFIG.ENDPOINTS.DESPESAS, dados);
      carregarAba('despesas');
    },
  });
}

function moeda(s) {
  const n = parseFloat(s);
  return isNaN(n) ? 'R$ 0,00' : n.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}
function dataBR(d) {
  if (!d) return '—';
  const data = String(d).split(' ')[0].split('-');
  return data.length === 3 ? `${data[2]}/${data[1]}/${data[0]}` : d;
}

async function carregarAba(aba) {
  const tbody = document.getElementById('tabela-fin');
  const thead = document.getElementById('thead-fin');
  mostrarMensagem(tbody, 'Carregando...', false, 5);

  const config = {
    caixas:   { endpoint: 'CAIXAS',   colunas: ['Caixa', 'Descrição', 'Saldo', 'Criado em', 'Ações'] },
    receitas: { endpoint: 'RECEITAS', colunas: ['ID', 'Valor', 'Forma', 'Observação', 'Ações'] },
    despesas: { endpoint: 'DESPESAS', colunas: ['ID', 'Valor', 'Tipo', 'Observação', 'Ações'] },
  }[aba];

  // Monta cabeçalho
  window.Dom.limpar(thead);
  const trh = window.Dom.criar('tr');
  config.colunas.forEach((c) => trh.appendChild(window.Dom.criarComTexto('th', 'tabela__th', c)));
  thead.appendChild(trh);

  try {
    const resposta = await window.Http.get(window.API_CONFIG.ENDPOINTS[config.endpoint]);
    const dados = Array.isArray(resposta.dados) ? resposta.dados : [];
    if (aba === 'caixas') renderCaixas(dados);
    else if (aba === 'receitas') renderReceitas(dados);
    else renderDespesas(dados);
  } catch (erro) {
    mostrarMensagem(tbody, erro.mensagem || 'Erro ao carregar.', true, 5);
  }
}

function tdExcluir(endpoint, id, rotulo, aba) {
  const td = window.Dom.criar('td', 'tabela__td tabela__acoes');
  const btn = window.Dom.criarComTexto('button', '', '\uD83D\uDDD1');
  btn.setAttribute('aria-label', 'Excluir');
  btn.addEventListener('click', async () => {
    if (!confirm(`Excluir ${rotulo}? Esta ação não pode ser desfeita.`)) return;
    try {
      await window.Http.delete(window.API_CONFIG.ENDPOINTS[endpoint] + '/' + id);
      carregarAba(aba);
    } catch (erro) {
      alert(erro.mensagem || 'Erro ao excluir.');
    }
  });
  td.appendChild(btn);
  return td;
}

function renderCaixas(lista) {
  const tbody = document.getElementById('tabela-fin');
  window.Dom.limpar(tbody);
  if (!lista.length) { mostrarMensagem(tbody, 'Nenhum caixa cadastrado.', false, 5); return; }
  lista.forEach((c) => {
    const tr = window.Dom.criar('tr', 'tabela__linha');
    tr.appendChild(window.Dom.td('#' + c.id_caixa, 'tabela__td tabela__nome'));
    tr.appendChild(window.Dom.td(c.descricao || '—'));
    tr.appendChild(window.Dom.td(moeda(c.saldo)));
    tr.appendChild(window.Dom.td(dataBR(c.criado_em)));
    tr.appendChild(tdExcluir('CAIXAS', c.id_caixa, `o caixa "${c.descricao}"`, 'caixas'));
    tbody.appendChild(tr);
  });
}

function renderReceitas(lista) {
  const tbody = document.getElementById('tabela-fin');
  window.Dom.limpar(tbody);
  if (!lista.length) { mostrarMensagem(tbody, 'Nenhuma receita registrada.', false, 5); return; }
  lista.forEach((r) => {
    const tr = window.Dom.criar('tr', 'tabela__linha');
    tr.appendChild(window.Dom.td('#' + (r.id_receita || r.id), 'tabela__td tabela__nome'));
    tr.appendChild(window.Dom.td(moeda(r.valor)));
    tr.appendChild(window.Dom.td(r.forma_pagamento || '—'));
    tr.appendChild(window.Dom.td(r.observacao || '—'));
    tr.appendChild(tdExcluir('RECEITAS', r.id_receita || r.id, `a receita #${r.id_receita || r.id}`, 'receitas'));
    tbody.appendChild(tr);
  });
}

function renderDespesas(lista) {
  const tbody = document.getElementById('tabela-fin');
  window.Dom.limpar(tbody);
  if (!lista.length) { mostrarMensagem(tbody, 'Nenhuma despesa registrada.', false, 5); return; }
  lista.forEach((d) => {
    const tr = window.Dom.criar('tr', 'tabela__linha');
    tr.appendChild(window.Dom.td('#' + (d.id_despesa || d.id), 'tabela__td tabela__nome'));
    tr.appendChild(window.Dom.td(moeda(d.valor)));
    tr.appendChild(window.Dom.td(d.tipo_movimentacao || '—'));
    tr.appendChild(window.Dom.td(d.observacao || '—'));
    tr.appendChild(tdExcluir('DESPESAS', d.id_despesa || d.id, `a despesa #${d.id_despesa || d.id}`, 'despesas'));
    tbody.appendChild(tr);
  });
}

function mostrarMensagem(tbody, texto, erro = false, colspan = 4) {
  window.Dom.limpar(tbody);
  const tr = window.Dom.criar('tr', 'tabela__linha');
  const td = window.Dom.td(texto, 'tabela__td');
  td.setAttribute('colspan', String(colspan));
  td.style.textAlign = 'center';
  td.style.color = erro ? 'var(--cor-erro)' : 'var(--cor-texto-suave)';
  tr.appendChild(td);
  tbody.appendChild(tr);
}
