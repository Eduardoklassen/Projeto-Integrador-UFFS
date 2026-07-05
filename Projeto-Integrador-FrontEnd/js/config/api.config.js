/* 
   API.CONFIG.JS — Configuração central da API
   Único lugar onde a URL e os endpoints do backend são
   definidos. Trocou de localhost p/ produção? Mexe só aqui.
*/

const API_CONFIG = {
  // URL base do backend PHP.
  // Ajuste a porta conforme o servidor (ex.: php -S localhost:8000).
  BASE_URL: 'http://localhost:8000',

  // Endpoints da API. Sempre relativos à BASE_URL.
  // Prefixo /api confirmado pela coleção do Insomnia (POST /api/login).
  ENDPOINTS: {
    LOGIN:   '/api/login',
    LOGOUT:  '/api/logout',
    DASHBOARD:      '/api/dashboard',
    PRODUTOS:       '/api/produtos',
    MATERIAIS:      '/api/materiais',
    CATEGORIAS:     '/api/categorias',
    PEDIDOS:        '/api/pedidos',
    COMPRAS:        '/api/compras',
    CLIENTES:       '/api/clientes',
    FORNECEDORES:   '/api/fornecedores',
    CAIXAS:         '/api/caixas',
    RECEITAS:       '/api/receitas',
    DESPESAS:       '/api/despesas',
    ANALISE:        '/api/analise',
    USUARIOS:       '/api/usuarios',
  },
};

// Disponibiliza globalmente (sem módulos ES, projeto vanilla).
window.API_CONFIG = API_CONFIG;
