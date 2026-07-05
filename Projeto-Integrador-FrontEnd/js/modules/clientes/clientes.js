/* 
  CLIENTES.JS — Controlador (INTEGRADO)
  
*/

let _clientes = [];

document.addEventListener('DOMContentLoaded', () => {
  window.Auth.exigirLogin('index.html');
  window.Layout.montar({ ativo: 'clientes', titulo: 'Clientes' });
  carregarClientes();
  document.getElementById('busca').addEventListener('input', (e) => filtrar(e.target.value));
  document.getElementById('btn-novo').addEventListener('click', () => abrirForm());
});

// Abre o formulário de cadastro (sem dados) ou edição (com cliente)
function abrirForm(cliente) {
  const edicao = !!cliente;
  window.FormModal.abrir({
    titulo: edicao ? 'Editar cliente' : 'Novo cliente',
    campos: [
      { nome: 'nome',     rotulo: 'Nome',      tipo: 'text',  obrigatorio: true, valor: cliente?.nome },
      { nome: 'email',    rotulo: 'E-mail',    tipo: 'email', valor: cliente?.email },
      { nome: 'telefone', rotulo: 'Telefone',  tipo: 'text',  valor: cliente?.telefone },
      { nome: 'cpf_cnpj', rotulo: 'CPF / CNPJ', tipo: 'text', obrigatorio: true, valor: cliente?.cpf_cnpj },
    ],
    aoSalvar: async (dados) => {
      if (edicao) {
        await window.Http.put(window.API_CONFIG.ENDPOINTS.CLIENTES + '/' + cliente.id_cliente, dados);
      } else {
        await window.Http.post(window.API_CONFIG.ENDPOINTS.CLIENTES, dados);
      }
      carregarClientes(); // recarrega a lista
    },
  });
}

// Confirma e exclui um cliente.
async function excluirCliente(cliente) {
  if (!confirm(`Excluir o cliente "${cliente.nome}"? Esta ação não pode ser desfeita.`)) return;
  try {
    await window.Http.delete(window.API_CONFIG.ENDPOINTS.CLIENTES + '/' + cliente.id_cliente);
    carregarClientes();
  } catch (erro) {
    alert(erro.mensagem || 'Erro ao excluir o cliente.');
  }
}

async function carregarClientes() {
  const tbody = document.getElementById('tabela-clientes');
  mostrarMensagem(tbody, 'Carregando clientes...');
  try {
    const resposta = await window.Http.get(window.API_CONFIG.ENDPOINTS.CLIENTES);
    _clientes = Array.isArray(resposta.dados) ? resposta.dados : [];
    renderClientes(_clientes);
  } catch (erro) {
    mostrarMensagem(tbody, erro.mensagem || 'Erro ao carregar clientes.', true);
  }
}

function renderClientes(lista) {
  const tbody = document.getElementById('tabela-clientes');
  window.Dom.limpar(tbody);
  if (!lista.length) { mostrarMensagem(tbody, 'Nenhum cliente encontrado.'); return; }

  lista.forEach((c) => {
    const tr = window.Dom.criar('tr', 'tabela__linha');
    tr.appendChild(window.Dom.td(c.nome, 'tabela__td tabela__nome'));
    tr.appendChild(window.Dom.td(c.email || '—'));
    tr.appendChild(window.Dom.td(c.telefone || '—'));
    tr.appendChild(window.Dom.td(c.cpf_cnpj || '—'));
    const tdAcoes = window.Dom.criar('td', 'tabela__td tabela__acoes');
    const btnEditar = window.Dom.criarComTexto('button', '', '✎');
    btnEditar.setAttribute('aria-label', 'Editar');
    btnEditar.addEventListener('click', () => abrirForm(c));
    const btnExcluir = window.Dom.criarComTexto('button', '', '🗑');
    btnExcluir.setAttribute('aria-label', 'Excluir');
    btnExcluir.addEventListener('click', () => excluirCliente(c));
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

function filtrar(termo) {
  const t = window.Validators.limpar(termo).toLowerCase();
  renderClientes(_clientes.filter((c) =>
    (c.nome || '').toLowerCase().includes(t) ||
    (c.email || '').toLowerCase().includes(t) ||
    (c.cpf_cnpj || '').includes(t)
  ));
}
