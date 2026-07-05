/* 
   MATERIAIS.JS — Controlador (INTEGRADO)

   GET /api/materiais. Campos: id_material, nome, unidade_medida
*/

let _materiais = [];

document.addEventListener('DOMContentLoaded', () => {
  window.Auth.exigirLogin('index.html');
  window.Layout.montar({ ativo: 'materiais', titulo: 'Materiais' });
  carregarMateriais();
  document.getElementById('busca').addEventListener('input', aplicarFiltros);
  document.getElementById('filtro-status').addEventListener('change', aplicarFiltros);
  document.getElementById('btn-novo').addEventListener('click', () => abrirForm());
});

function abrirForm(material) {
  const edicao = !!material;
  window.FormModal.abrir({
    titulo: edicao ? 'Editar material' : 'Novo material',
    campos: [
      { nome: 'nome',           rotulo: 'Nome',          tipo: 'text',   obrigatorio: true, valor: material?.nome },
      { nome: 'unidade_medida', rotulo: 'Unidade',       tipo: 'select', valor: material?.unidade_medida,
        opcoes: [{ valor: 'm3', rotulo: 'm³' }, { valor: 'sc', rotulo: 'Saco' }, { valor: 'un', rotulo: 'Unidade' }, { valor: 'kg', rotulo: 'Kg' }, { valor: 't', rotulo: 'Tonelada' }] },
      { nome: 'custo_unitario', rotulo: 'Custo (R$)',    tipo: 'number', obrigatorio: true, valor: material?.custo_unitario },
      { nome: 'estoque',        rotulo: 'Estoque',       tipo: 'number', obrigatorio: true, valor: material?.estoque },
      { nome: 'ativo',          rotulo: 'Situação',      tipo: 'select', valor: material ? String(material.ativo) : '1',
        opcoes: [{ valor: '1', rotulo: 'Ativo' }, { valor: '0', rotulo: 'Inativo' }] },
    ],
    aoSalvar: async (dados) => {
      dados.ativo = Number(dados.ativo);
      if (edicao) {
        await window.Http.put(window.API_CONFIG.ENDPOINTS.MATERIAIS + '/' + material.id_material, dados);
      } else {
        await window.Http.post(window.API_CONFIG.ENDPOINTS.MATERIAIS, dados);
      }
      carregarMateriais();
    },
  });
}

async function excluirMaterial(material) {
  if (!confirm(`Excluir o material "${material.nome}"? Esta ação não pode ser desfeita.`)) return;
  try {
    await window.Http.delete(window.API_CONFIG.ENDPOINTS.MATERIAIS + '/' + material.id_material);
    carregarMateriais();
  } catch (erro) {
    alert(erro.mensagem || 'Erro ao excluir o material.');
  }
}

async function carregarMateriais() {
  const tbody = document.getElementById('tabela-materiais');
  mostrarMensagem(tbody, 'Carregando materiais...');
  try {
    const resposta = await window.Http.get(window.API_CONFIG.ENDPOINTS.MATERIAIS);
    _materiais = Array.isArray(resposta.dados) ? resposta.dados : [];
    renderMateriais(_materiais);
  } catch (erro) {
    mostrarMensagem(tbody, erro.mensagem || 'Erro ao carregar materiais.', true);
  }
}

function moeda(s) {
  const n = parseFloat(s);
  return isNaN(n) ? '—' : n.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function unidadeLonga(u) {
  const mapa = { m3: 'm³', sc: 'saco(s)', un: 'unidade(s)', kg: 'kg', t: 'tonelada(s)' };
  return mapa[u] || u;
}

function renderMateriais(lista) {
  const tbody = document.getElementById('tabela-materiais');
  window.Dom.limpar(tbody);
  if (!lista.length) { mostrarMensagem(tbody, 'Nenhum material encontrado.'); return; }

  lista.forEach((m) => {
    const tr = window.Dom.criar('tr', 'tabela__linha');

    // Material: nome (+ esgotado se estoque 0)
    const tdNome = window.Dom.criar('td', 'tabela__td');
    tdNome.appendChild(window.Dom.criarComTexto('div', 'tabela__nome', m.nome));
    if (parseFloat(m.estoque) === 0) {
      tdNome.appendChild(window.Dom.criarComTexto('div', 'materiais__unidade', '⚠ Sem estoque'));
    }
    tr.appendChild(tdNome);

    tr.appendChild(window.Dom.td(unidadeLonga(m.unidade_medida)));
    tr.appendChild(window.Dom.td(moeda(m.custo_unitario)));
    tr.appendChild(window.Dom.td(parseFloat(m.estoque).toLocaleString('pt-BR')));

    const ativo = Number(m.ativo) === 1;
    tr.appendChild(window.Dom.tdBadge(ativo ? 'Ativo' : 'Inativo', ativo ? 'badge--sucesso' : 'badge--erro'));

    const tdAcoes = window.Dom.criar('td', 'tabela__td tabela__acoes');
    const btnEditar = window.Dom.criarComTexto('button', '', '✎');
    btnEditar.setAttribute('aria-label', 'Editar');
    btnEditar.addEventListener('click', () => abrirForm(m));
    const btnExcluir = window.Dom.criarComTexto('button', '', '🗑');
    btnExcluir.setAttribute('aria-label', 'Excluir');
    btnExcluir.addEventListener('click', () => excluirMaterial(m));
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
  const status = document.getElementById('filtro-status').value;
  renderMateriais(_materiais.filter((m) => {
    const bateTexto = (m.nome || '').toLowerCase().includes(t);
    const bateStatus = (status === 'todos') || (String(Number(m.ativo)) === status);
    return bateTexto && bateStatus;
  }));
}
