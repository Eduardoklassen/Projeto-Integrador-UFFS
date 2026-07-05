/* 
  PRODUTOS.JS — Controlador da página Produtos (INTEGRADO)
  
  Lista produtos vindos da API real (GET /api/produtos).
  Campos reais do back: id_produto, nome, preco_final (string),
  estoque, observacao, ativo
 
*/

let _produtos = [];
// Estado da ordenação: por qual campo e em que direção.
let _ordem = { campo: null, asc: true };

document.addEventListener('DOMContentLoaded', () => {
  window.Auth.exigirLogin('index.html');
  window.Layout.montar({ ativo: 'produtos', titulo: 'Produtos' });

  carregarProdutos();

  document.getElementById('busca').addEventListener('input', aplicarFiltros);
  document.getElementById('filtro-status').addEventListener('change', aplicarFiltros);
  document.getElementById('btn-novo').addEventListener('click', () => abrirForm());
  ligarOrdenacao();
});

/**
 * Ordenação clicável nos cabeçalhos marcados com data-ordenar.
 * clicar em "Produto", "Estoque" ou "Preço final" ordena a
 * lista por aquele campo; clicar de novo inverte (crescente/decrescente).
 */
function ligarOrdenacao() {
  document.querySelectorAll('.tabela__th--ordenavel').forEach((th) => {
    th.addEventListener('click', () => {
      const campo = th.dataset.ordenar;
      // mesmo campo: inverte direção; campo novo: começa crescente.
      _ordem.asc = _ordem.campo === campo ? !_ordem.asc : true;
      _ordem.campo = campo;
      atualizarIndicadores();
      aplicarFiltros();
    });
  });
}

function atualizarIndicadores() {
  document.querySelectorAll('.tabela__th--ordenavel').forEach((th) => {
    const base = th.textContent.replace(/[▲▼]\s*$/, '').trim();
    th.textContent = th.dataset.ordenar === _ordem.campo
      ? `${base} ${_ordem.asc ? '▲' : '▼'}`
      : base;
  });
}


function ordenarLista(lista) {
  if (!_ordem.campo) return lista;
  const campo = _ordem.campo;
  const numerico = campo === 'estoque' || campo === 'preco_final';
  return [...lista].sort((a, b) => {
    let va = a[campo], vb = b[campo];
    if (numerico) {
      va = parseFloat(va) || 0; vb = parseFloat(vb) || 0;
      return _ordem.asc ? va - vb : vb - va;
    }
    va = (va || '').toString().toLowerCase();
    vb = (vb || '').toString().toLowerCase();
    return _ordem.asc ? va.localeCompare(vb) : vb.localeCompare(va);
  });
}

function abrirForm(produto) {
  const edicao = !!produto;
  window.FormModal.abrir({
    titulo: edicao ? 'Editar produto' : 'Novo produto',
    campos: [
      { nome: 'nome',        rotulo: 'Nome',        tipo: 'text',   obrigatorio: true, valor: produto?.nome },
      { nome: 'preco_final', rotulo: 'Preço (R$)',  tipo: 'number', obrigatorio: true, valor: produto?.preco_final },
      { nome: 'estoque',     rotulo: 'Estoque',     tipo: 'number', obrigatorio: true, valor: produto?.estoque },
      { nome: 'observacao',  rotulo: 'Observação',  tipo: 'text',   valor: produto?.observacao },
      { nome: 'ativo',       rotulo: 'Situação',    tipo: 'select', valor: produto ? String(produto.ativo) : '1',
        opcoes: [{ valor: '1', rotulo: 'Ativo' }, { valor: '0', rotulo: 'Inativo' }] },
    ],
    aoSalvar: async (dados) => {
      dados.ativo = Number(dados.ativo);
      if (edicao) {
        await window.Http.put(window.API_CONFIG.ENDPOINTS.PRODUTOS + '/' + produto.id_produto, dados);
      } else {
        await window.Http.post(window.API_CONFIG.ENDPOINTS.PRODUTOS, dados);
      }
      carregarProdutos();
    },
  });
}

async function excluirProduto(produto) {
  if (!confirm(`Excluir o produto "${produto.nome}"? Esta ação não pode ser desfeita.`)) return;
  try {
    await window.Http.delete(window.API_CONFIG.ENDPOINTS.PRODUTOS + '/' + produto.id_produto);
    carregarProdutos();
  } catch (erro) {
    alert(erro.mensagem || 'Erro ao excluir o produto.');
  }
}

async function carregarProdutos() {
  const tbody = document.getElementById('tabela-produtos');
  mostrarMensagem(tbody, 'Carregando produtos...');

  try {
    // Integração real com o backend.
    const resposta = await window.Http.get(window.API_CONFIG.ENDPOINTS.PRODUTOS);
    _produtos = Array.isArray(resposta.dados) ? resposta.dados : [];
    aplicarFiltros();
  } catch (erro) {
    mostrarMensagem(tbody, erro.mensagem || 'Erro ao carregar produtos.', true);
  }
}

function moeda(valorString) {
  const num = parseFloat(valorString);
  if (isNaN(num)) return '—';
  return num.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function renderProdutos(lista) {
  const tbody = document.getElementById('tabela-produtos');
  window.Dom.limpar(tbody);

  if (!lista.length) {
    mostrarMensagem(tbody, 'Nenhum produto encontrado.');
    return;
  }

  lista.forEach((p) => {
    const tr = window.Dom.criar('tr', 'tabela__linha');

    // Produto: nome (aviso de esgotado, se aplicável)
    const tdProduto = window.Dom.criar('td', 'tabela__td');
    tdProduto.appendChild(window.Dom.criarComTexto('div', 'tabela__nome', p.nome));
    if (Number(p.estoque) === 0) {
      tdProduto.appendChild(window.Dom.criarComTexto('div', 'produtos__codigo', '⚠ Esgotado'));
    }
    tr.appendChild(tdProduto);

    // Observação
    tr.appendChild(window.Dom.td(p.observacao || '—'));

    // Estoque
    tr.appendChild(window.Dom.td(Number(p.estoque).toLocaleString('pt-BR')));

    // Preço (preco_final vem como string)
    tr.appendChild(window.Dom.td(moeda(p.preco_final)));

    // Status (campo ativo: 1 = Ativo, 0 = Inativo)
    const ativo = Number(p.ativo) === 1;
    tr.appendChild(window.Dom.tdBadge(
      ativo ? 'Ativo' : 'Inativo',
      ativo ? 'badge--sucesso' : 'badge--erro'
    ));

    // Ações
    const tdAcoes = window.Dom.criar('td', 'tabela__td tabela__acoes');
    const btnEditar = window.Dom.criarComTexto('button', '', '✎');
    btnEditar.setAttribute('aria-label', 'Editar');
    btnEditar.addEventListener('click', () => abrirForm(p));
    const btnExcluir = window.Dom.criarComTexto('button', '', '🗑');
    btnExcluir.setAttribute('aria-label', 'Excluir');
    btnExcluir.addEventListener('click', () => excluirProduto(p));
    tdAcoes.append(btnEditar, btnExcluir);
    tr.appendChild(tdAcoes);

    tbody.appendChild(tr);
  });
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

function aplicarFiltros() {
  const t = window.Validators.limpar(document.getElementById('busca').value).toLowerCase();
  const status = document.getElementById('filtro-status').value; // 'todos' | '1' | '0'
  const filtrados = _produtos.filter((p) => {
    const bateTexto =
      (p.nome || '').toLowerCase().includes(t) ||
      (p.observacao || '').toLowerCase().includes(t);
    const bateStatus = (status === 'todos') || (String(Number(p.ativo)) === status);
    return bateTexto && bateStatus;
  });
  renderProdutos(ordenarLista(filtrados));
}
