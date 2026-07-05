# Segurança — Projeto Integrador (Sistema de Estoque Nino Klassen)

Este documento registra as decisões e práticas de segurança do sistema,
separando o que é responsabilidade do **frontend** e do **backend**, e o que
já está implementado vs. pendente. Serve de checklist e de apoio para a
apresentação do projeto.

---

## 1. Frontend — o que está implementado

| Prática | Onde | Status |
|---|---|---|
| Anti-XSS com `textContent` (nunca `innerHTML` com dado externo) | `login.js`, todo o JS | ✅ |
| Token JWT centralizado num único módulo | `storage.js` | ✅ |
| Token anexado automaticamente (`Authorization: Bearer`) | `http.js` | ✅ |
| `JSON.parse` protegido contra dado corrompido | `storage.js`, `http.js` | ✅ |
| Tratamento de resposta não-JSON do servidor | `http.js` | ✅ |
| Validação de formato (e-mail, campos obrigatórios) | `validators.js`, `login.js` | ✅ |
| Sanitização de entrada (trim, remoção de caracteres de controle, limite) | `validators.js` | ✅ |
| Erros sem vazar detalhes técnicos ao usuário | `http.js` | ✅ |
| CSS load order e separação de responsabilidades | `index.html` | ✅ |

---

## 2. Frontend — decisão consciente: token no localStorage

**Decisão:** o token JWT é guardado no `localStorage`.

**Trade-off conhecido:**
- ✅ Simples, padrão didático, fácil de implementar e explicar.
- ⚠️ Se houver uma falha de XSS na aplicação, um script malicioso pode ler o
  `localStorage` e roubar o token.

**Alternativa mais segura (não adotada agora):** o backend enviar o token em
um **cookie `HttpOnly`**, que o JavaScript não consegue ler — o que elimina o
roubo via XSS, mas exige defesa contra CSRF e muda a lógica do `http.js`.

**Justificativa:** para o escopo de um Projeto Integrador, `localStorage` +
JWT é aceitável. A defesa real contra o risco é **não ter XSS** — por isso o
uso rigoroso de `textContent`. Em um sistema de produção de alto risco, a
escolha seria cookie `HttpOnly`.

---

## 3. Frontend — o que NÃO é responsabilidade dele

O frontend roda no navegador do usuário e é **totalmente manipulável**
(DevTools, proxies, etc.). Portanto, ele **nunca** é a fonte de verdade de
segurança. As validações do front servem para experiência do usuário e higiene;
quem protege de fato é o backend. Itens abaixo são do back:

- Autenticação e geração/validação do JWT
- Autorização (quem pode acessar o quê — níveis de acesso)
- Validação e sanitização definitiva de TODA entrada
- Proteção contra SQL Injection
- Hash de senhas
- Configuração de CORS
- Rate limiting / proteção contra força bruta

---

## 4. Backend — checklist (verificar no projeto PHP)

| Prática | Por quê | Verificar |
|---|---|---|
| **Prepared statements (PDO)** em todas as queries | Previne SQL Injection | ⬜ |
| **`password_hash()` / `password_verify()`** | Senhas nunca em texto puro | ⬜ |
| **Validação de entrada no servidor** | Front é manipulável | ⬜ |
| **JWT com expiração curta** (`exp`) | Limita janela de um token roubado | ⬜ |
| **Segredo do JWT forte e fora do código** (`.env`) | Evita forjar tokens | ⬜ |
| **`.env` fora do Git** (`.gitignore`) | Não vazar credenciais | ⬜ |
| **CORS restrito** (só a origem do front, não `*`) | Evita uso por sites terceiros | ⬜ |
| **HTTPS em produção** | Token/senha não trafegam em texto puro | ⬜ |
| **Verificação de autorização por rota** | Operador ≠ Admin | ⬜ |
| **Tratamento de erro sem stack trace ao cliente** | Não vazar estrutura interna | ⬜ |
| **Limite de tamanho de upload** (boletos) | Evita abuso de armazenamento | ⬜ |
| **Validação de tipo de arquivo no upload** (PDF/imagem) | Evita upload de script malicioso | ⬜ |

---

## 5. Pendências de segurança ligadas a features futuras

- **Recuperação de senha:** token de reset com validade curta, uso único,
  enviado por e-mail. Nunca revelar se um e-mail existe ou não (evita
  enumeração de usuários).
- **Upload de boletos:** validar tipo (MIME real, não só extensão), limitar
  tamanho, armazenar fora da raiz pública ou com nomes não-adivinháveis.
- **Exportação CSV:** cuidado com "CSV injection" — campos que começam com
  `=`, `+`, `-`, `@` podem virar fórmulas no Excel. Prefixar com aspa simples
  ao exportar.

---

## 6. Referências

- OWASP Top 10 (referência geral de riscos web)
- Handout 08 (DOM), seção 5.1 — XSS e `textContent` vs `innerHTML`
- Handout 07 (CSS), seção 16.3 — alerta sobre injeção em CSS dinâmico
- Handout 06 (HTML), seção 9 — alerta sobre concatenar dados do usuário em HTML

---

## 7. Recomendações de segurança da API (BACKEND — a implementar depois)

Análise de boas práticas de JWT/API (origem: material de referência sobre
segurança de APIs). Todas são do BACKEND (PHP), não do frontend.
Priorizadas por valor × esforço para o contexto deste Projeto Integrador.

### A implementar (alto valor, viável)

| # | Recomendação | Por quê | Como (PHP) |
|---|---|---|---|
| 1 | **Algoritmo explícito na verificação do JWT** | Sem fixar o algoritmo, existe o ataque "alg: none" (token forjado sem assinatura) ou confusão HS256/RS256 | Na firebase/php-jwt: `JWT::decode($token, new Key($secret, 'HS256'))` — conferir se já está assim |
| 5 | **Rate limit no endpoint de login** | Sem isso, brute-force de senha é trivial | Contar tentativas por IP (tabela ou arquivo); bloquear após N falhas/minuto; retornar HTTP 429 |
| 6 | **Erro genérico ao cliente + log interno detalhado** | Não dar pistas ao atacante (expirado? assinatura? claim?) | Cliente recebe "credenciais inválidas"; servidor registra o motivo exato em log |
| 7 | **Secret forte, fora do código, no .env** | Evita forjar tokens; evita vazamento no Git | JÁ FEITO — manter o secret longo/aleatório e o .env no .gitignore |

### Opcional (bom, mas não crítico aqui)

| # | Recomendação | Observação |
|---|---|---|
| 2 | Validação de claims (issuer/audience) | Útil com múltiplas APIs/apps; ganho pequeno num sistema único |

### Fora do escopo do Projeto Integrador (mencionar como evolução futura)

| # | Recomendação | Por que fica de fora |
|---|---|---|
| 3 | Access token curto (15 min) + refresh token em cookie httpOnly | Padrão de produção, mas exige 2 tokens, rota de refresh e lógica no front — complexidade alta |
| 4 | Blacklist de token revogado (JTI no Redis com TTL) | Resolve logout/revogação real, mas exige Redis (serviço extra além do MySQL) |

### Frontend (o que dá pra fazer no cliente para acompanhar)

- Tratar respostas 401 (token expirado/inválido) → redirecionar ao login.
- Tratar resposta 429 (rate limit) → mensagem "muitas tentativas, aguarde".
- Estas melhorias do cliente complementam o backend, mas não substituem a
  proteção que precisa existir no servidor.
