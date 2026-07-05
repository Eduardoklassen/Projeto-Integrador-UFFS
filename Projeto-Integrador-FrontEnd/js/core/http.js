/* 
   HTTP.JS — Cliente HTTP 

   O "coração" da comunicação com a API. TODA chamada ao
   backend passa por aqui. Responsável por:
     - montar a URL completa
     - anexar o token JWT automaticamente
     -S enviar/receber JSON
     - padronizar o tratamento de erros
*/

const Http = {
  /**
   * Método interno genérico. Os atalhos (get, post...) usam este.
   * @param {string} endpoint - caminho relativo (ex.: '/login')
   * @param {object} opcoes   - { metodo, corpo, autenticar }
   */
  async requisicao(endpoint, { metodo = 'GET', corpo = null, autenticar = true } = {}) {
    const url = window.API_CONFIG.BASE_URL + endpoint;

    const cabecalhos = {
      'Content-Type': 'application/json',
    };

    // Anexa o token JWT automaticamente, se existir e for necessário.
    if (autenticar) {
      const token = window.Storage.obterToken();
      if (token) {
        cabecalhos['Authorization'] = `Bearer ${token}`;
      }
    }

    const config = { method: metodo, headers: cabecalhos };
    if (corpo !== null) {
      config.body = JSON.stringify(corpo);
    }

    try {
      const resposta = await fetch(url, config);

      // Tenta ler JSON; se vier vazio, usa objeto neutro.
      // Protege contra resposta não-JSON (ex.: página de erro HTML do PHP):
      // nesse caso, não quebra — devolve erro padronizado.
      let dados = {};
      const texto = await resposta.text();
      if (texto) {
        try {
          dados = JSON.parse(texto);
        } catch (erroParse) {
          throw {
            status: resposta.status,
            mensagem: 'Resposta inválida do servidor.',
            dados: null,
          };
        }
      }

      // O backend retorna { sucesso, mensagem, dados }.
      // Se o status HTTP não for OK, lançamos um erro padronizado.
      if (!resposta.ok) {
        // 401 em rota autenticada = token expirado ou inválido.
        // Limpa a sessão e manda para o login (exceto na própria
        // tela de login, onde 401 significa credenciais erradas).
        if (resposta.status === 401 && autenticar) {
          window.Storage.limparTudo();
          // Evita laço caso já esteja no login.
          if (!window.location.pathname.endsWith('index.html') &&
              window.location.pathname !== '/') {
            // leva o motivo na URL (sessao=expirada).
            // o token agora expira em 15 min de sessão;
            // o porquê. A tela de login lê o parâmetro e explica.
            window.location.href = 'index.html?sessao=expirada';
          }
        }
        // 429 = rate limit (muitas tentativas de login). Repassa a
        // mensagem do back, que já é amigável ("aguarde alguns minutos").
        throw {
          status: resposta.status,
          mensagem: dados.mensagem || 'Erro ao comunicar com o servidor.',
          dados: dados.dados || null,
        };
      }

      return dados;
    } catch (erro) {
      // Erro de rede (servidor desligado, CORS, sem internet).
      if (erro instanceof TypeError) {
        throw {
          status: 0,
          mensagem: 'Não foi possível conectar ao servidor. Verifique se a API está rodando.',
          dados: null,
        };
      }
      // Repassa o erro já padronizado.
      throw erro;
    }
  },

  /* ---------- Atalhos ---------- */
  get(endpoint, opcoes = {}) {
    return this.requisicao(endpoint, { ...opcoes, metodo: 'GET' });
  },

  post(endpoint, corpo, opcoes = {}) {
    return this.requisicao(endpoint, { ...opcoes, metodo: 'POST', corpo });
  },

  put(endpoint, corpo, opcoes = {}) {
    return this.requisicao(endpoint, { ...opcoes, metodo: 'PUT', corpo });
  },

  delete(endpoint, opcoes = {}) {
    return this.requisicao(endpoint, { ...opcoes, metodo: 'DELETE' });
  },
};

window.Http = Http;
