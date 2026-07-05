/* 
   FORM-MODAL.JS — Formulário de cadastro/edição em modal
   
   Gera um formulário a partir de uma definição de campos e o
   exibe no Modal. Coleta os dados, valida obrigatórios e chama
   um callback de salvamento (que faz o POST/PUT).

   SEGURANÇA: campos criados via createElement; valores tratados
   como texto. Sanitização aplicada na coleta (Validators.limpar).
*/

const FormModal = {
  /**
   * Abre um formulário no modal.
   * @param {object} opts
   * (faz o POST/PUT)
   */
  abrir({ titulo, campos, aoSalvar }) {
    const form = window.Dom.criar('form');
    form.setAttribute('novalidate', '');

    const grid = window.Dom.criar('div', 'form-grid-2');

    campos.forEach((campo) => {
      const wrap = window.Dom.criar('div', 'form__campo');

      const label = window.Dom.criar('label', 'form__label');
      label.setAttribute('for', 'fm-' + campo.nome);
      label.appendChild(document.createTextNode(campo.rotulo + ' '));
      if (campo.obrigatorio) {
        label.appendChild(window.Dom.criarComTexto('span', 'form__obrigatorio', '*'));
      }
      wrap.appendChild(label);

      let input;
      if (campo.tipo === 'select') {
        input = window.Dom.criar('select', 'form__select');
        (campo.opcoes || []).forEach((op) => {
          const opt = window.Dom.criar('option');
          opt.value = (typeof op === 'object') ? op.valor : op;
          opt.textContent = (typeof op === 'object') ? op.rotulo : op;
          input.appendChild(opt);
        });
      } else if (campo.tipo === 'textarea') {
        input = window.Dom.criar('textarea', 'form__textarea');
      } else {
        input = window.Dom.criar('input', 'form__input');
        input.type = campo.tipo || 'text';
      }
      input.id = 'fm-' + campo.nome;
      input.name = campo.nome;
      if (campo.valor !== undefined && campo.valor !== null) input.value = campo.valor;
      if (campo.placeholder) input.placeholder = campo.placeholder;
      wrap.appendChild(input);

      grid.appendChild(wrap);
    });

    form.appendChild(grid);

    // Mensagem de erro/estado
    const msg = window.Dom.criar('p', '');
    msg.style.fontSize = '0.85rem';
    msg.style.minHeight = '1.2em';
    form.appendChild(msg);

    // Ações
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
      msg.style.color = 'var(--cor-erro)';

      // Coleta + sanitização
      const dados = {};
      for (const campo of campos) {
        let valor = form.elements[campo.nome].value;
        if (typeof valor === 'string') valor = window.Validators.limpar(valor);
        if (campo.obrigatorio && !valor) {
          msg.textContent = `O campo "${campo.rotulo}" é obrigatório.`;
          return;
        }
        dados[campo.nome] = valor;
      }

      btnSalvar.disabled = true;
      btnSalvar.textContent = 'Salvando...';
      try {
        await aoSalvar(dados);
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

window.FormModal = FormModal;
