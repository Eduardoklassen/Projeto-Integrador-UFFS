/* 
  COMPRAS.JS — Controlador (INTEGRADO)
 
  GET /api/compras
  
*/

let _compras = [];

document.addEventListener('DOMContentLoaded', () => {
  window.Auth.exigirLogin('index.html');
  window.Layout.montar({ ativo: 'compras', titulo: 'Compras' });
  carregarCompras();
  document.getElementById('busca').addEventListener('input', (e) => filtrar(e.target.value));
  document.getElementById('btn-novo').addEventListener('click', () => abrirNovaCompra());
});

async function carregarCompras() {
  const tbody = document.getElementById('tabela-compras');
  mostrarMensagem(tbody, 'Carregando compras...');
  try {
    const resposta = await window.Http.get(window.API_CONFIG.ENDPOINTS.COMPRAS);
    _compras = Array.isArray(resposta.dados) ? resposta.dados : [];
    renderCompras(_compras);
  } catch (erro) {
    mostrarMensagem(tbody, erro.mensagem || 'Erro ao carregar compras.', true);
  }
}

function moeda(s) {
  const n = parseFloat(s);
  return isNaN(n) ? '—' : n.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}
function dataBR(d) {
  if (!d) return '—';
  const p = String(d).split('-');
  return p.length === 3 ? `${p[2]}/${p[1]}/${p[0]}` : d;
}

function renderCompras(lista) {
  const tbody = document.getElementById('tabela-compras');
  window.Dom.limpar(tbody);
  if (!lista.length) { mostrarMensagem(tbody, 'Nenhuma compra encontrada.'); return; }

  lista.forEach((c) => {
    const tr = window.Dom.criar('tr', 'tabela__linha');
    tr.appendChild(window.Dom.td('#' + c.id_compra, 'tabela__td tabela__nome'));
    tr.appendChild(window.Dom.td(c.fornecedor_nome || '—'));
    tr.appendChild(window.Dom.td(dataBR(c.data_compra)));
    tr.appendChild(window.Dom.td(moeda(c.valor_total)));
    const tdAcoes = window.Dom.criar('td', 'tabela__td tabela__acoes');
    const btnVer = window.Dom.criarComTexto('button', '', '👁');
    btnVer.setAttribute('aria-label', 'Ver detalhes');
    btnVer.addEventListener('click', () => verCompra(c.id_compra));
    tdAcoes.appendChild(btnVer);
    // o back tem DELETE /api/compras/id. 
  
    const btnExcluir = window.Dom.criarComTexto('button', '', '🗑');
    btnExcluir.setAttribute('aria-label', 'Excluir');
    btnExcluir.addEventListener('click', () => excluirCompra(c));
    tdAcoes.appendChild(btnExcluir);
    tr.appendChild(tdAcoes);
    tbody.appendChild(tr);
  });
}

// Confirma e exclui uma compra 
async function excluirCompra(c) {
  if (!confirm(`Excluir a compra #${c.id_compra} (${c.fornecedor_nome || 'sem fornecedor'})? Esta ação não pode ser desfeita.`)) return;
  try {
    await window.Http.delete(window.API_CONFIG.ENDPOINTS.COMPRAS + '/' + c.id_compra);
    carregarCompras();
  } catch (erro) {
    alert(erro.mensagem || 'Erro ao excluir a compra.');
  }
}

async function abrirNovaCompra() {
  let fornecedores, materiais;
  try {
    const [rf, rm] = await Promise.all([
      window.Http.get(window.API_CONFIG.ENDPOINTS.FORNECEDORES),
      window.Http.get(window.API_CONFIG.ENDPOINTS.MATERIAIS),
    ]);
    fornecedores = (Array.isArray(rf.dados) ? rf.dados : [])
      .filter((f) => Number(f.ativo) === 1); // só compra de fornecedor ativo
    materiais = (Array.isArray(rm.dados) ? rm.dados : [])
      .filter((m) => Number(m.ativo) === 1);
  } catch (e) {
    alert(e.mensagem || 'Erro ao carregar fornecedores/materiais.');
    return;
  }
  if (!fornecedores.length) { alert('Cadastre um fornecedor ativo antes de registrar uma compra.'); return; }
  if (!materiais.length) { alert('Cadastre um material ativo antes de registrar uma compra.'); return; }

  const UNIDADES = { m3: 'm³', sc: 'sc', un: 'un', kg: 'kg', t: 't' };

  window.FormItens.abrir({
    titulo: 'Nova compra',
    principal: {
      nome: 'id_fornecedor', rotulo: 'Fornecedor',
      opcoes: fornecedores.map((f) => ({ valor: String(f.id_fornecedor), rotulo: f.nome })),
    },
    item: {
      nome: 'id_material', rotulo: 'Material', rotuloValor: 'Custo unit. (R$)', qtdFracionada: true,
      opcoes: materiais.map((m) => ({
        valor: String(m.id_material),
        rotulo: `${m.nome} (${UNIDADES[m.unidade_medida] || m.unidade_medida})`,
        preco: m.custo_unitario,
      })),
    },
    aoSalvar: async ({ principal, itens }) => {
      await window.Http.post(window.API_CONFIG.ENDPOINTS.COMPRAS, {
        id_fornecedor: principal,
        itens: itens.map((i) => ({
          id_material: i.id,
          quantidade: i.quantidade,
          custo_unitario: i.valor,
        })),
      });
      carregarCompras();
    },
  });
}

// Busca o detalhe da compra e abre o modal com os materiais
async function verCompra(id) {
  try {
    const resposta = await window.Http.get(window.API_CONFIG.ENDPOINTS.COMPRAS + '/' + id);
    const c = resposta.dados;
    const partes = [];

    const sInfo = window.Dom.criar('div', 'modal__secao');
    sInfo.appendChild(window.Dom.criarComTexto('div', 'modal__secao-titulo', 'Informações'));
    const info = window.Dom.criar('div', 'modal__info');
    [
      ['Fornecedor', c.fornecedor_nome || '—'],
      ['Data', dataBR(c.data_compra)],
      ['Valor total', moeda(c.valor_total)],
    ].forEach(([rot, val]) => {
      const item = window.Dom.criar('div', 'modal__info-item');
      item.appendChild(window.Dom.criarComTexto('div', 'modal__info-rotulo', rot));
      item.appendChild(window.Dom.criarComTexto('div', 'modal__info-valor', val));
      info.appendChild(item);
    });
    sInfo.appendChild(info);
    partes.push(sInfo);

    const sItens = window.Dom.criar('div', 'modal__secao');
    sItens.appendChild(window.Dom.criarComTexto('div', 'modal__secao-titulo', 'Materiais comprados'));
    sItens.appendChild(tabelaItens(c.itens || [], [
      ['Material', (i) => i.material_nome],
      ['Qtd.', (i) => String(i.quantidade)],
      ['Custo unit.', (i) => moeda(i.custo_unitario)],
      ['Subtotal', (i) => moeda(i.sub_total)],
    ]));
    partes.push(sItens);

    window.Modal.abrir('Compra #' + c.id_compra, partes);
  } catch (erro) {
    alert(erro.mensagem || 'Erro ao carregar a compra.');
  }
}

//  Monta uma tabela simples a partir de colunas [titulo, funcaoValor]
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
  td.setAttribute('colspan', '5');
  td.style.textAlign = 'center';
  td.style.color = erro ? 'var(--cor-erro)' : 'var(--cor-texto-suave)';
  tr.appendChild(td);
  tbody.appendChild(tr);
}

function filtrar(termo) {
  const t = window.Validators.limpar(termo).toLowerCase();
  renderCompras(_compras.filter((c) => (c.fornecedor_nome || '').toLowerCase().includes(t)));
}
