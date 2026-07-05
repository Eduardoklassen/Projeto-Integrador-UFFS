/* 
   VALIDATORS.JS — Validações de formulário
  
   Funções puras de validação reutilizáveis. Cada função
   retorna string vazia ('') quando válido, ou a mensagem
   de erro quando inválido.
*/

const Validators = {
  obrigatorio(valor, nomeCampo = 'Campo') {
    if (!valor || valor.trim() === '') {
      return `${nomeCampo} é obrigatório.`;
    }
    return '';
  },

  // Valida formato de e-mail
  email(valor) {
    const padrao = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!padrao.test(valor)) {
      return 'Informe um e-mail válido.';
    }
    return '';
  },

  minimo(valor, min, nomeCampo = 'Campo') {
    if (valor.length < min) {
      return `${nomeCampo} deve ter no mínimo ${min} caracteres.`;
    }
    return '';
  },

   // Executa várias validações em sequência e retorna
   // a primeira mensagem de erro encontrada (ou '').
  
  combinar(valor, regras) {
    for (const regra of regras) {
      const erro = regra(valor);
      if (erro) return erro;
    }
    return '';
  },


  // Remove espaços nas pontas e caracteres de controle invisíveis
  limpar(valor) {
    if (typeof valor !== 'string') return '';
    // Remove caracteres de controle (exceto espaços normais) e apara as pontas.
    return valor.replace(/[\u0000-\u001F\u007F]/g, '').trim();
  },

  // Limita o tamanho máximo de uma string (evita payloads gigantes)
  limitar(valor, max = 255) {
    const limpo = this.limpar(valor);
    return limpo.slice(0, max);
  },
};

window.Validators = Validators;
