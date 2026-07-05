/* 
   FORM-ITENS.JS — Formulário com itens dinâmicos (pedido/compra)
  
   modal de cadastro composto por um select principal
   (cliente ou fornecedor) e linhas de itens que o usuário
   adiciona/remove (produto/material, quantidade, valor unit.),
   com subtotal por linha e total geral calculados ao vivo.

   um componente próprio: o FormModal é uma lista fixa
   de campos — não comporta linhas repetíveis. Pedido e Compra
   têm exatamente a mesma estrutura (1 entidade + N itens),
   então um único componente atende os dois sem duplicação.
   SEGURANÇA: tudo via createElement/textContent (anti-XSS),
   valores numéricos convertidos com Number() antes do envio.
*/

const FormItens = {
  /**
   * @param {object} opts
   */
  abrir({ titulo, principal, item, aoSalvar }) {
    const form = window.Dom.criar('form');
    form.setAttribute('novalidate', '');

    //Select principal (cliente/fornecedor) 
    const campoPrincipal = window.Dom.criar('div', 'form__campo');
    const labelP = window.Dom.criar('label', 'form__label');
    labelP.appendChild(document.createTextNode(principal.rotulo + ' '));
    labelP.appendChild(window.Dom.criarComTexto('span', 'form__obrigatorio', '*'));
    const selectP = window.Dom.criar('select', 'form__select');
    const optVazia = window.Dom.criar('option');
    optVazia.value = '';
    optVazia.textContent = 'Selecione...';
    selectP.appendChild(optVazia);
    principal.opcoes.forEach((op) => {
      const o = window.Dom.criar('option');
      o.value = op.valor;
      o.textContent = op.rotulo;
      selectP.appendChild(o);
    });
    campoPrincipal.append(labelP, selectP);
    form.appendChild(campoPrincipal);

    // Cabeçalho da lista de itens 
    const tituloItens = window.Dom.criarComTexto('div', 'form-itens__titulo', 'Itens');
    form.appendChild(tituloItens);

    const cab = window.Dom.criar('div', 'form-itens__linha form-itens__linha--cab');
    [item.rotulo, 'Qtd.', item.rotuloValor, 'Subtotal', ''].forEach((t) => {
      cab.appendChild(window.Dom.criarComTexto('span', 'form-itens__cab', t));
    });
    form.appendChild(cab);

    const listaItens = window.Dom.criar('div');
    form.appendChild(listaItens);

    //Total 
    const totalWrap = window.Dom.criar('div', 'form-itens__total');
    totalWrap.appendChild(window.Dom.criarComTexto('span', '', 'Total: '));
    const totalValor = window.Dom.criarComTexto('strong', '', 'R$ 0,00');
    totalWrap.appendChild(totalValor);
    form.appendChild(totalWrap);

    const moeda = (n) => (isNaN(n) ? 'R$ 0,00' :
      n.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }));

    function recalcular() {
      let total = 0;
      listaItens.querySelectorAll('.form-itens__linha').forEach((linha) => {
        const qtd = Number(linha.querySelector('.fi-qtd').value) || 0;
        const val = Number(linha.querySelector('.fi-valor').value) || 0;
        const sub = qtd * val;
        linha.querySelector('.fi-subtotal').textContent = moeda(sub);
        total += sub;
      });
      totalValor.textContent = moeda(total);
    }

    // Cria uma linha de item 
    function adicionarLinha() {
      const linha = window.Dom.criar('div', 'form-itens__linha');

      const sel = window.Dom.criar('select', 'form__select fi-item');
      const ov = window.Dom.criar('option');
      ov.value = '';
      ov.textContent = 'Selecione...';
      sel.appendChild(ov);
      item.opcoes.forEach((op) => {
        const o = window.Dom.criar('option');
        o.value = op.valor;
        o.textContent = op.rotulo;
        o.dataset.preco = op.preco ?? '';
        sel.appendChild(o);
      });

      const qtd = window.Dom.criar('input', 'form__input fi-qtd');
      qtd.type = 'number';
      // Compras aceitam quantidade fracionada (0,5 m³ de areia);
      // pedidos vendem unidades inteiras. O chamador decide.
      if (item.qtdFracionada) { qtd.min = '0.01'; qtd.step = '0.01'; }
      else { qtd.min = '1'; qtd.step = '1'; }
      qtd.value = '1';

      const valor = window.Dom.criar('input', 'form__input fi-valor');
      valor.type = 'number';
      valor.min = '0';
      valor.step = '0.01';

      const subtotal = window.Dom.criarComTexto('span', 'fi-subtotal', 'R$ 0,00');

      const remover = window.Dom.criarComTexto('button', 'form-itens__remover', '✕');
      remover.type = 'button';
      remover.setAttribute('aria-label', 'Remover item');
      remover.addEventListener('click', () => { linha.remove(); recalcular(); });

      // Ao escolher o item, sugere o preço cadastrado (editável —
      // o vendedor pode negociar; o valor registrado é o praticado).
      sel.addEventListener('change', () => {
        const preco = sel.selectedOptions[0]?.dataset.preco;
        if (preco) valor.value = preco;
        recalcular();
      });
      qtd.addEventListener('input', recalcular);
      valor.addEventListener('input', recalcular);

      linha.append(sel, qtd, valor, subtotal, remover);
      listaItens.appendChild(linha);
    }

    // Botão adicionar item 
    const btnAdd = window.Dom.criarComTexto('button', 'btn btn--neutro btn--auto', '＋ Adicionar item');
    btnAdd.type = 'button';
    btnAdd.style.marginTop = '0.5rem';
    btnAdd.addEventListener('click', adicionarLinha);
    form.appendChild(btnAdd);

    adicionarLinha(); // começa com uma linha

    // Mensagem de erro 
    const msg = window.Dom.criar('p', '');
    msg.style.fontSize = '0.85rem';
    msg.style.minHeight = '1.2em';
    msg.style.color = 'var(--cor-erro)';
    form.appendChild(msg);

    //Ações 
    const acoes = window.Dom.criar('div', 'form__acoes');
    const btnCancelar = window.Dom.criarComTexto('button', 'btn btn--neutro btn--auto', 'Cancelar');
    btnCancelar.type = 'button';
    btnCancelar.addEventListener('click', () => window.Modal.fechar());
    const btnSalvar = window.Dom.criarComTexto('button', 'btn btn--primario btn--auto', 'Salvar');
    btnSalvar.type = 'submit';
    acoes.append(btnCancelar, btnSalvar);
    form.appendChild(acoes);

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      msg.textContent = '';

      if (!selectP.value) {
        msg.textContent = `Selecione o campo "${principal.rotulo}".`;
        return;
      }

      const itens = [];
      let erroLinha = '';
      listaItens.querySelectorAll('.form-itens__linha').forEach((linha) => {
        const idItem = linha.querySelector('.fi-item').value;
        const qtd = Number(linha.querySelector('.fi-qtd').value);
        const val = Number(linha.querySelector('.fi-valor').value);
        if (!idItem) { erroLinha = 'Há um item sem seleção.'; return; }
        if (!qtd || qtd <= 0) { erroLinha = 'Quantidade deve ser maior que zero.'; return; }
        if (isNaN(val) || val < 0) { erroLinha = 'Informe o valor unitário do item.'; return; }
        itens.push({ id: Number(idItem), quantidade: qtd, valor: val });
      });

      if (erroLinha) { msg.textContent = erroLinha; return; }
      if (!itens.length) { msg.textContent = 'Adicione pelo menos um item.'; return; }

      btnSalvar.disabled = true;
      btnSalvar.textContent = 'Salvando...';
      try {
        await aoSalvar({ principal: Number(selectP.value), itens });
        window.Modal.fechar();
      } catch (erro) {
        msg.textContent = erro.mensagem || 'Erro ao salvar.';
        btnSalvar.disabled = false;
        btnSalvar.textContent = 'Salvar';
      }
    });

    window.Modal.abrir(titulo, form);
  },
};

window.FormItens = FormItens;
