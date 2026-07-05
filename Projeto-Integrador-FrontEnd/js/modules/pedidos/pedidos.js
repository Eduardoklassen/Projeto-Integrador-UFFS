/* 
  PEDIDOS.JS — Controlador (INTEGRADO)
  
  GET /api/pedidos (vendas/saídas). Campos: id_pedido, status,
  data_pedido, valor_total, cliente_nome, usuario_nome.
  
*/

let _pedidos = [];
let _ordem = { campo: null, asc: true };

document.addEventListener('DOMContentLoaded', () => {
  window.Auth.exigirLogin('index.html');
  window.Layout.montar({ ativo: 'pedidos', titulo: 'Pedidos' });
  carregarPedidos();
  document.getElementById('busca').addEventListener('input', (e) => filtrar(e.target.value));
  document.getElementById('btn-novo').addEventListener('click', () => abrirNovoPedido());
  ligarOrdenacao();
});

/**
 * Requisito: ordenação por pelo menos dois campos. Clicar inverte a
 * direção. Ordena a lista local, respeitando o filtro de busca ativo.
 */
function ligarOrdenacao() {
  document.querySelectorAll('.tabela__th--ordenavel').forEach((th) => {
    th.addEventListener('click', () => {
      const campo = th.dataset.ordenar;
      _ordem.asc = _ordem.campo === campo ? !_ordem.asc : true;
      _ordem.campo = campo;
      document.querySelectorAll('.tabela__th--ordenavel').forEach((h) => {
        const base = h.textContent.replace(/[▲▼]\s*$/, '').trim();
        h.textContent = h.dataset.ordenar === _ordem.campo
          ? `${base} ${_ordem.asc ? '▲' : '▼'}` : base;
      });
      filtrar(document.getElementById('busca').value);
    });
  });
}

// Ordena conforme _ordem: data, valor numérico ou texto. 
function ordenarLista(lista) {
  if (!_ordem.campo) return lista;
  const campo = _ordem.campo;
  return [...lista].sort((a, b) => {
    let va = a[campo], vb = b[campo];
    if (campo === 'valor_total') {
      va = parseFloat(va) || 0; vb = parseFloat(vb) || 0;
      return _ordem.asc ? va - vb : vb - va;
    }
    if (campo === 'data_pedido') {
      va = new Date(va); vb = new Date(vb);
      return _ordem.asc ? va - vb : vb - va;
    }
    va = (va || '').toString().toLowerCase();
    vb = (vb || '').toString().toLowerCase();
    return _ordem.asc ? va.localeCompare(vb) : vb.localeCompare(va);
  });
}

async function abrirNovoPedido() {
  let clientes, produtos;
  try {
    const [rc, rp] = await Promise.all([
      window.Http.get(window.API_CONFIG.ENDPOINTS.CLIENTES),
      window.Http.get(window.API_CONFIG.ENDPOINTS.PRODUTOS),
    ]);
    clientes = Array.isArray(rc.dados) ? rc.dados : [];
    produtos = (Array.isArray(rp.dados) ? rp.dados : [])
      .filter((p) => Number(p.ativo) === 1); // só vende produto ativo
  } catch (e) {
    alert(e.mensagem || 'Erro ao carregar clientes/produtos.');
    return;
  }
  if (!clientes.length) { alert('Cadastre um cliente antes de criar um pedido.'); return; }
  if (!produtos.length) { alert('Nenhum produto ativo disponível para venda.'); return; }

  window.FormItens.abrir({
    titulo: 'Novo pedido',
    principal: {
      nome: 'id_cliente', rotulo: 'Cliente',
      opcoes: clientes.map((c) => ({ valor: String(c.id_cliente), rotulo: c.nome })),
    },
    item: {
      nome: 'id_produto', rotulo: 'Produto', rotuloValor: 'Valor unit. (R$)',
      opcoes: produtos.map((p) => ({
        valor: String(p.id_produto),
        rotulo: `${p.nome} (estoque: ${p.estoque})`,
        preco: p.preco_final,
      })),
    },
    aoSalvar: async ({ principal, itens }) => {
      await window.Http.post(window.API_CONFIG.ENDPOINTS.PEDIDOS, {
        id_cliente: principal,
        itens: itens.map((i) => ({
          id_produto: i.id,
          quantidade: i.quantidade,
          valor_unitario: i.valor,
        })),
      });
      carregarPedidos();
    },
  });
}

async function carregarPedidos() {
  const tbody = document.getElementById('tabela-pedidos');
  mostrarMensagem(tbody, 'Carregando pedidos...');
  try {
    const resposta = await window.Http.get(window.API_CONFIG.ENDPOINTS.PEDIDOS);
    _pedidos = Array.isArray(resposta.dados) ? resposta.dados : [];
    filtrar(document.getElementById("busca").value);
  } catch (erro) {
    mostrarMensagem(tbody, erro.mensagem || 'Erro ao carregar pedidos.', true);
  }
}

function moeda(s) {
  const n = parseFloat(s);
  return isNaN(n) ? '—' : n.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function dataBR(d) {
  if (!d) return '—';
  const partes = String(d).split('-');
  return partes.length === 3 ? `${partes[2]}/${partes[1]}/${partes[0]}` : d;
}

function badgeStatus(status) {
  const mapa = {
    aberto:    'badge--info',
    pago:      'badge--sucesso',
    enviado:   'badge--info',
    cancelado: 'badge--erro',
    entregue:  'badge--sucesso',
  };
  return mapa[status] || 'badge--info';
}

function renderPedidos(lista) {
  const tbody = document.getElementById('tabela-pedidos');
  window.Dom.limpar(tbody);
  if (!lista.length) { mostrarMensagem(tbody, 'Nenhum pedido encontrado.'); return; }

  lista.forEach((p) => {
    const tr = window.Dom.criar('tr', 'tabela__linha');
    tr.appendChild(window.Dom.td('#' + p.id_pedido, 'tabela__td tabela__nome'));
    tr.appendChild(window.Dom.td(p.cliente_nome || '—'));
    tr.appendChild(window.Dom.td(dataBR(p.data_pedido)));
    tr.appendChild(window.Dom.td(moeda(p.valor_total)));

    const statusTexto = (p.status || '').charAt(0).toUpperCase() + (p.status || '').slice(1);
    tr.appendChild(window.Dom.tdBadge(statusTexto || '—', badgeStatus(p.status)));

    const tdAcoes = window.Dom.criar('td', 'tabela__td tabela__acoes');
    const btnVer = window.Dom.criarComTexto('button', '', '👁');
    btnVer.setAttribute('aria-label', 'Ver detalhes');
    btnVer.addEventListener('click', () => verPedido(p.id_pedido));
    const btnEditar = window.Dom.criarComTexto('button', '', '✎');
    btnEditar.setAttribute('aria-label', 'Editar status');
    btnEditar.addEventListener('click', () => abrirEdicaoStatus(p));
    const btnExcluir = window.Dom.criarComTexto('button', '', '🗑');
    btnExcluir.setAttribute('aria-label', 'Excluir');
    btnExcluir.addEventListener('click', () => excluirPedido(p));
    tdAcoes.append(btnVer, btnEditar, btnExcluir);
    tr.appendChild(tdAcoes);
    tbody.appendChild(tr);
  });
}

function abrirEdicaoStatus(p) {
  window.FormModal.abrir({
    titulo: `Pedido #${p.id_pedido} — alterar status`,
    campos: [
      { nome: 'status', rotulo: 'Status', tipo: 'select', obrigatorio: true, valor: p.status,
        opcoes: [
          { valor: 'aberto',    rotulo: 'Aberto' },
          { valor: 'pago',      rotulo: 'Pago' },
          { valor: 'enviado',   rotulo: 'Enviado' },
          { valor: 'entregue',  rotulo: 'Entregue' },
          { valor: 'cancelado', rotulo: 'Cancelado' },
        ] },
    ],
    aoSalvar: async (dados) => {
      await window.Http.put(window.API_CONFIG.ENDPOINTS.PEDIDOS + '/' + p.id_pedido, dados);
      carregarPedidos();
    },
  });
}

// Confirma e exclui um pedido (DELETE — rota exige admin)
async function excluirPedido(p) {
  if (!confirm(`Excluir o pedido #${p.id_pedido} (${p.cliente_nome || 'sem cliente'})? Esta ação não pode ser desfeita.`)) return;
  try {
    await window.Http.delete(window.API_CONFIG.ENDPOINTS.PEDIDOS + '/' + p.id_pedido);
    carregarPedidos();
  } catch (erro) {
    alert(erro.mensagem || 'Erro ao excluir o pedido.');
  }
}

// Busca o detalhe do pedido e abre o modal com itens + histórico. 
async function verPedido(id) {
  try {
    const resposta = await window.Http.get(window.API_CONFIG.ENDPOINTS.PEDIDOS + '/' + id);
    const p = resposta.dados;
    const partes = [];

    // Informações gerais
    const sInfo = window.Dom.criar('div', 'modal__secao');
    sInfo.appendChild(window.Dom.criarComTexto('div', 'modal__secao-titulo', 'Informações'));
    const info = window.Dom.criar('div', 'modal__info');
    [
      ['Cliente', p.cliente_nome || '—'],
      ['Responsável', p.usuario_nome || '—'],
      ['Data', dataBR(p.data_pedido)],
      ['Status', (p.status || '—')],
      ['Valor total', moeda(p.valor_total)],
    ].forEach(([rot, val]) => {
      const item = window.Dom.criar('div', 'modal__info-item');
      item.appendChild(window.Dom.criarComTexto('div', 'modal__info-rotulo', rot));
      item.appendChild(window.Dom.criarComTexto('div', 'modal__info-valor', val));
      info.appendChild(item);
    });
    sInfo.appendChild(info);
    partes.push(sInfo);

    // Itens do pedido
    const sItens = window.Dom.criar('div', 'modal__secao');
    sItens.appendChild(window.Dom.criarComTexto('div', 'modal__secao-titulo', 'Itens'));
    sItens.appendChild(tabelaItens(p.itens || [], [
      ['Produto', (i) => i.produto_nome],
      ['Qtd.', (i) => String(i.quantidade)],
      ['Valor unit.', (i) => moeda(i.valor_unitario)],
      ['Subtotal', (i) => moeda(i.sub_total)],
    ]));
    partes.push(sItens);

    // Histórico de status
    if (Array.isArray(p.historico) && p.historico.length) {
      const sHist = window.Dom.criar('div', 'modal__secao');
      sHist.appendChild(window.Dom.criarComTexto('div', 'modal__secao-titulo', 'Histórico de status'));
      sHist.appendChild(tabelaItens(p.historico, [
        ['De', (h) => h.status_anterior || '—'],
        ['Para', (h) => h.status_novo || '—'],
        ['Quando', (h) => dataBR((h.data_mudanca || '').split(' ')[0])],
      ]));
      partes.push(sHist);
    }

    window.Modal.abrir('Pedido #' + p.id_pedido, partes);
  } catch (erro) {
    alert(erro.mensagem || 'Erro ao carregar o pedido.');
  }
}

// Monta uma tabela simples a partir de colunas 
function tabelaItens(lista, colunas) {
  const wrap = window.Dom.criar('div', 'tabela-scroll');
  const tabela = window.Dom.criar('table', 'tabela');
  const thead = window.Dom.criar('thead');
  const trh = window.Dom.criar('tr');
  colunas.forEach(([titulo]) => trh.appendChild(window.Dom.criarComTexto('th', 'tabela__th', titulo)));
  thead.appendChild(trh);
  const tbody = window.Dom.criar('tbody');
  if (!lista.length) {
    const tr = window.Dom.criar('tr', 'tabela__linha');
    const td = window.Dom.td('Sem itens.', 'tabela__td');
    td.setAttribute('colspan', String(colunas.length));
    td.style.textAlign = 'center';
    td.style.color = 'var(--cor-texto-suave)';
    tr.appendChild(td);
    tbody.appendChild(tr);
  } else {
    lista.forEach((item) => {
      const tr = window.Dom.criar('tr', 'tabela__linha');
      colunas.forEach(([, fn]) => tr.appendChild(window.Dom.td(fn(item))));
      tbody.appendChild(tr);
    });
  }
  tabela.append(thead, tbody);
  wrap.appendChild(tabela);
  return wrap;
}

function mostrarMensagem(tbody, texto, erro = false) {
  window.Dom.limpar(tbody);
  const tr = window.Dom.criar('tr', 'tabela__linha');
  const td = window.Dom.td(texto, 'tabela__td');
  td.setAttribute('colspan', '6');
  td.style.textAlign = 'center';
  td.style.color = erro ? 'var(--cor-erro)' : 'var(--cor-texto-suave)';
  tr.appendChild(td);
  tbody.appendChild(tr);
}

function filtrar(termo) {
  const t = window.Validators.limpar(termo).toLowerCase();
  const filtrados = _pedidos.filter((p) =>
    (p.cliente_nome || '').toLowerCase().includes(t) ||
    (p.status || '').toLowerCase().includes(t)
  );
  renderPedidos(ordenarLista(filtrados));
}
