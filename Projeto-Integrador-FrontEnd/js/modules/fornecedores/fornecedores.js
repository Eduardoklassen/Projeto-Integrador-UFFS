/* 
   FORNECEDORES.JS — Controlador (INTEGRADO)

   GET /api/fornecedores (rota limpa, sem params — busca local).
   Campos: id_fornecedor, nome, tipo_pessoa, cpf_cnpj, email,
   telefone, endereco, ativo, criado_em.
*/

let _fornecedores = [];

document.addEventListener('DOMContentLoaded', () => {
  window.Auth.exigirLogin('index.html');
  window.Layout.montar({ ativo: 'fornecedores', titulo: 'Fornecedores' });

  carregarFornecedores();
  document.getElementById('busca').addEventListener('input', aplicarFiltros);
  document.getElementById('filtro-status').addEventListener('change', aplicarFiltros);
  document.getElementById('btn-novo').addEventListener('click', () => abrirForm());
});

function abrirForm(forn) {
  const edicao = !!forn;
  window.FormModal.abrir({
    titulo: edicao ? 'Editar fornecedor' : 'Novo fornecedor',
    campos: [
      { nome: 'nome',        rotulo: 'Nome / Razão social', tipo: 'text', obrigatorio: true, valor: forn?.nome },
      { nome: 'tipo_pessoa', rotulo: 'Tipo de pessoa', tipo: 'select', valor: forn?.tipo_pessoa,
        opcoes: [{ valor: 'juridica', rotulo: 'Pessoa Jurídica' }, { valor: 'fisica', rotulo: 'Pessoa Física' }] },
      { nome: 'cpf_cnpj',    rotulo: 'CPF / CNPJ', tipo: 'text', obrigatorio: true, valor: forn?.cpf_cnpj },
      { nome: 'email',       rotulo: 'E-mail', tipo: 'email', valor: forn?.email },
      { nome: 'telefone',    rotulo: 'Telefone', tipo: 'text', valor: forn?.telefone },
      { nome: 'endereco',    rotulo: 'Endereço', tipo: 'text', valor: forn?.endereco },
      { nome: 'ativo',       rotulo: 'Situação', tipo: 'select', valor: forn ? String(Number(forn.ativo)) : '1',
        opcoes: [{ valor: '1', rotulo: 'Ativo' }, { valor: '0', rotulo: 'Inativo' }] },
    ],
    aoSalvar: async (dados) => {
      dados.ativo = Number(dados.ativo);
      if (edicao) {
        await window.Http.put(window.API_CONFIG.ENDPOINTS.FORNECEDORES + '/' + forn.id_fornecedor, dados);
      } else {
        await window.Http.post(window.API_CONFIG.ENDPOINTS.FORNECEDORES, dados);
      }
      carregarFornecedores();
    },
  });
}

async function excluirFornecedor(forn) {
  if (!confirm(`Excluir o fornecedor "${forn.nome}"? Esta ação não pode ser desfeita.`)) return;
  try {
    await window.Http.delete(window.API_CONFIG.ENDPOINTS.FORNECEDORES + '/' + forn.id_fornecedor);
    carregarFornecedores();
  } catch (erro) {
    alert(erro.mensagem || 'Erro ao excluir o fornecedor.');
  }
}

async function carregarFornecedores() {
  const tbody = document.getElementById('tabela-fornecedores');
  mostrarMensagem(tbody, 'Carregando fornecedores...');
  try {
    const resposta = await window.Http.get(window.API_CONFIG.ENDPOINTS.FORNECEDORES);
    _fornecedores = Array.isArray(resposta.dados) ? resposta.dados : [];
    renderFornecedores(_fornecedores);
  } catch (erro) {
    mostrarMensagem(tbody, erro.mensagem || 'Erro ao carregar fornecedores.', true);
  }
}

function renderFornecedores(lista) {
  const tbody = document.getElementById('tabela-fornecedores');
  window.Dom.limpar(tbody);

  if (!lista.length) { mostrarMensagem(tbody, 'Nenhum fornecedor encontrado.'); return; }

  lista.forEach((f) => {
    const tr = window.Dom.criar('tr', 'tabela__linha');

    // Fornecedor: nome + tipo de pessoa
    const tdNome = window.Dom.criar('td', 'tabela__td');
    tdNome.appendChild(window.Dom.criarComTexto('div', 'tabela__nome', f.nome));
    tdNome.appendChild(window.Dom.criarComTexto('div', 'fornecedores__sub',
      f.tipo_pessoa === 'juridica' ? 'Pessoa Jurídica' : 'Pessoa Física'));
    tr.appendChild(tdNome);

    tr.appendChild(window.Dom.td(f.cpf_cnpj || '—'));

    // Contato: email + telefone
    const tdContato = window.Dom.criar('td', 'tabela__td');
    tdContato.appendChild(window.Dom.criarComTexto('div', '', f.email || '—'));
    tdContato.appendChild(window.Dom.criarComTexto('div', 'fornecedores__sub', f.telefone || '—'));
    tr.appendChild(tdContato);

    // Status (ativo)
    const ativo = Number(f.ativo) === 1;
    tr.appendChild(window.Dom.tdBadge(ativo ? 'Ativo' : 'Inativo', ativo ? 'badge--sucesso' : 'badge--erro'));

    // Ações
    const tdAcoes = window.Dom.criar('td', 'tabela__td tabela__acoes');
    const btnEditar = window.Dom.criarComTexto('button', '', '✎');
    btnEditar.setAttribute('aria-label', 'Editar');
    btnEditar.addEventListener('click', () => abrirForm(f));
    const btnExcluir = window.Dom.criarComTexto('button', '', '🗑');
    btnExcluir.setAttribute('aria-label', 'Excluir');
    btnExcluir.addEventListener('click', () => excluirFornecedor(f));
    tdAcoes.append(btnEditar, btnExcluir);
    tr.appendChild(tdAcoes);

    tbody.appendChild(tr);
  });
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

function aplicarFiltros() {
  const t = window.Validators.limpar(document.getElementById('busca').value).toLowerCase();
  const status = document.getElementById('filtro-status').value;
  renderFornecedores(_fornecedores.filter((f) => {
    const bateTexto = (f.nome || '').toLowerCase().includes(t) || (f.cpf_cnpj || '').includes(t);
    const bateStatus = (status === 'todos') || (String(Number(f.ativo)) === status);
    return bateTexto && bateStatus;
  }));
}
