/* 
  USUARIOS.JS — CRUD de usuários (INTEGRADO à API real)
   
  Lista, cadastra, edita e exclui usuários via /api/usuarios.
  Todas as rotas são restritas a admin no backend; esta tela
  também só abre para admin

*/

let _usuarios = [];

document.addEventListener('DOMContentLoaded', () => {
  window.Auth.exigirLogin('index.html');
  window.Layout.montar({ ativo: 'usuarios', titulo: 'Usuários' });

  // Apoio visual: se não for admin, nem carrega a tela 
  // devolveria 403 de qualquer forma).
  const atual = window.Auth.usuarioAtual();
  if (atual && atual.tipo && atual.tipo !== 'admin') {
    avisarSemPermissao();
    return;
  }

  carregarUsuarios();

  document.getElementById('busca').addEventListener('input', (e) => filtrar(e.target.value));
  document.getElementById('btn-novo').addEventListener('click', () => abrirForm());
});

async function carregarUsuarios() {
  const tbody = document.getElementById('tabela-usuarios');
  window.Dom.limpar(tbody);
  const trCarregando = window.Dom.criar('tr', 'tabela__linha');
  const tdCarregando = window.Dom.td('Carregando...', 'tabela__td');
  tdCarregando.setAttribute('colspan', '4');
  tdCarregando.style.textAlign = 'center';
  trCarregando.appendChild(tdCarregando);
  tbody.appendChild(trCarregando);

  try {
    const resposta = await window.Http.get(window.API_CONFIG.ENDPOINTS.USUARIOS);
    _usuarios = Array.isArray(resposta.dados) ? resposta.dados : [];
    renderUsuarios(_usuarios);
  } catch (erro) {
    window.Dom.limpar(tbody);
    const tr = window.Dom.criar('tr', 'tabela__linha');
    const td = window.Dom.td(erro.mensagem || 'Erro ao carregar usuários.', 'tabela__td');
    td.setAttribute('colspan', '4');
    td.style.textAlign = 'center';
    td.style.color = 'var(--cor-erro)';
    tr.appendChild(td);
    tbody.appendChild(tr);
  }
}
 
function abrirForm(usuario) {
  const edicao = !!usuario;
  window.FormModal.abrir({
    titulo: edicao ? 'Editar usuário' : 'Novo usuário',
    campos: [
      { nome: 'nome_usuario', rotulo: 'Nome', tipo: 'text', obrigatorio: true, valor: usuario?.nome_usuario },
      { nome: 'email', rotulo: 'E-mail', tipo: 'email', obrigatorio: true, valor: usuario?.email },
      { nome: 'senha', rotulo: edicao ? 'Senha (deixe em branco para manter)' : 'Senha',
        tipo: 'password', obrigatorio: !edicao },
      { nome: 'tipo_usuario', rotulo: 'Nível de acesso', tipo: 'select',
        valor: usuario?.tipo_usuario || 'comum',
        opcoes: [{ valor: 'comum', rotulo: 'Operador (comum)' }, { valor: 'admin', rotulo: 'Administrador' }] },
    ],
    aoSalvar: async (dados) => {
      try {
        if (edicao) {
          await window.Http.put(window.API_CONFIG.ENDPOINTS.USUARIOS + '/' + usuario.id_usuario, dados);
        } else {
          await window.Http.post(window.API_CONFIG.ENDPOINTS.USUARIOS, dados);
        }
        carregarUsuarios();
      } catch (erro) {
        if (erro.status === 403) {
          alert('Apenas administradores podem gerenciar usuários.');
        } else {
          throw erro; // o FormModal mostra a mensagem (e-mail duplicado, etc.)
        }
      }
    },
  });
}

// Confirma e exclui um usuário. As regras de proteção (não excluir
// a si mesmo o último admin) são garantidas pelo backend
async function excluirUsuario(u) {
  if (!confirm(`Excluir o usuário "${u.nome_usuario}"? Esta ação não pode ser desfeita.`)) return;
  try {
    await window.Http.delete(window.API_CONFIG.ENDPOINTS.USUARIOS + '/' + u.id_usuario);
    carregarUsuarios();
  } catch (erro) {
    // 409 = regra de negócio (último admin, própria conta, vínculos).
    alert(erro.mensagem || 'Erro ao excluir o usuário.');
  }
}

function avisarSemPermissao() {
  const main = window.Dom.$('.app__main');
  window.Dom.limpar(main);
  const card = window.Dom.criar('section', 'card');
  card.appendChild(window.Dom.criarComTexto('h2', 'card__titulo', 'Acesso restrito'));
  card.appendChild(window.Dom.criarComTexto('p', '',
    'Apenas administradores podem gerenciar usuários.'));
  main.appendChild(card);
}

function renderUsuarios(lista) {
  const tbody = document.getElementById('tabela-usuarios');
  window.Dom.limpar(tbody);

  if (!lista.length) {
    const tr = window.Dom.criar('tr', 'tabela__linha');
    const td = window.Dom.td('Nenhum usuário encontrado.', 'tabela__td');
    td.setAttribute('colspan', '4');
    td.style.textAlign = 'center';
    td.style.color = 'var(--cor-texto-suave)';
    tr.appendChild(td);
    tbody.appendChild(tr);
    return;
  }

  const meuId = window.Auth.usuarioAtual()?.id;

  lista.forEach((u) => {
    const tr = window.Dom.criar('tr', 'tabela__linha');

    tr.appendChild(window.Dom.td(u.nome_usuario, 'tabela__td tabela__nome'));
    tr.appendChild(window.Dom.td(u.email, 'tabela__td usuarios__email'));

    const tdNivel = window.Dom.criar('td', 'tabela__td');
    const ehAdmin = u.tipo_usuario === 'admin';
    const nivel = window.Dom.criarComTexto('span',
      `nivel ${ehAdmin ? 'nivel--admin' : 'nivel--operador'}`,
      ehAdmin ? 'Administrador' : 'Operador');
    tdNivel.appendChild(nivel);
    tr.appendChild(tdNivel);

  
    const tdAcoes = window.Dom.criar('td', 'tabela__td tabela__acoes');
    const btnEditar = window.Dom.criarComTexto('button', '', '\u270E');
    btnEditar.setAttribute('aria-label', 'Editar');
    btnEditar.addEventListener('click', () => abrirForm(u));
    tdAcoes.appendChild(btnEditar);

    if (Number(u.id_usuario) !== Number(meuId)) {
      const btnExcluir = window.Dom.criarComTexto('button', '', '\uD83D\uDDD1');
      btnExcluir.setAttribute('aria-label', 'Excluir');
      btnExcluir.addEventListener('click', () => excluirUsuario(u));
      tdAcoes.appendChild(btnExcluir);
    }

    tr.appendChild(tdAcoes);
    tbody.appendChild(tr);
  });
}

function filtrar(termo) {
  const t = window.Validators.limpar(termo).toLowerCase();
  renderUsuarios(_usuarios.filter((u) =>
    (u.nome_usuario || '').toLowerCase().includes(t) ||
    (u.email || '').toLowerCase().includes(t)
  ));
}
