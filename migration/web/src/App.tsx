import { useEffect, useState, type FormEvent } from "react";

type Appearance = "roxo" | "azul" | "branco" | "dourado" | "laranja" | "bordo" | "esmeralda";
type Theme = "light" | "dark";
type Book = { id: number; titulo: string; autor: string; isbn: string | null; categoria: string | null; total_exemplares: number; disponiveis: number };
type User = { id: number; nome: string; matricula: string; email: string | null; tipo: string };
const API_URL = (import.meta.env.VITE_API_URL || "").replace(/\/$/, "");
const appearances: { id: Appearance; label: string }[] = [
  { id: "roxo", label: "Roxo discreto" }, { id: "azul", label: "Azul" },
  { id: "branco", label: "Prata claro" }, { id: "dourado", label: "Dourado" },
  { id: "laranja", label: "Laranja" }, { id: "bordo", label: "Bordô" },
  { id: "esmeralda", label: "Verde esmeralda" }
];
const nav = [
  { group: "VISÃO GERAL", items: [{ id: "inicio", label: "Início", icon: "⌂" }, { id: "emprestimos", label: "Empréstimos", icon: "⇄" }] },
  { group: "ACERVO", items: [{ id: "catalogo", label: "Buscar livros", icon: "⌕" }, { id: "livros", label: "Gerenciar livros", icon: "▤" }, { id: "reservas", label: "Reservas e fila", icon: "◷" }] },
  { group: "GESTÃO", items: [{ id: "alunos", label: "Alunos", icon: "♙" }, { id: "devolucoes", label: "Devoluções", icon: "↩" }, { id: "relatorios", label: "Relatórios", icon: "▥" }] }
];

export default function App() {
  const [theme, setTheme] = useState<Theme>(() => (localStorage.getItem("be-theme") as Theme) || "light");
  const [appearance, setAppearance] = useState<Appearance>(() => (localStorage.getItem("be-appearance") as Appearance) || "branco");
  const [collapsed, setCollapsed] = useState(false);
  const [active, setActive] = useState("inicio");
  const [token, setToken] = useState(() => sessionStorage.getItem("be-token") || "");
  const [user, setUser] = useState<User | null>(null);
  const [identifier, setIdentifier] = useState("");
  const [password, setPassword] = useState("");
  const [loginBusy, setLoginBusy] = useState(false);
  const [query, setQuery] = useState("");
  const [books, setBooks] = useState<Book[]>([]);
  const [catalogBusy, setCatalogBusy] = useState(false);
  const [error, setError] = useState("");
  const [notice, setNotice] = useState("");

  useEffect(() => {
    document.documentElement.dataset.theme = theme;
    document.documentElement.dataset.appearance = appearance;
    localStorage.setItem("be-theme", theme);
    localStorage.setItem("be-appearance", appearance);
  }, [theme, appearance]);

  useEffect(() => {
    if (!token || !API_URL) return;
    fetch(API_URL + "/api/v1/auth/eu", { headers: { Authorization: "Bearer " + token } })
      .then(async response => {
        const body = await response.json();
        if (!response.ok) throw new Error(body.erro || "Sessão inválida.");
        setUser(body.dados);
      })
      .catch(() => { sessionStorage.removeItem("be-token"); setToken(""); setUser(null); });
  }, [token]);

  async function handleLogin(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError("");
    if (!API_URL) { setError("A API ainda não foi configurada. Defina VITE_API_URL no ambiente do frontend."); return; }
    setLoginBusy(true);
    try {
      const response = await fetch(API_URL + "/api/v1/auth/login", {
        method: "POST", headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ identificador: identifier, senha: password })
      });
      const body = await response.json();
      if (!response.ok || !body.sucesso) throw new Error(body.erro || "Não foi possível entrar.");
      sessionStorage.setItem("be-token", body.dados.token);
      setToken(body.dados.token);
      setUser(body.dados.usuario);
      setPassword("");
      setNotice("Login realizado. A sessão ficará ativa nesta aba.");
    } catch (err) { setError(err instanceof Error ? err.message : "Falha de conexão com a API."); }
    finally { setLoginBusy(false); }
  }

  async function loadCatalog(searchTerm = query) {
    setError(""); setNotice("");
    if (!token) { setError("Entre na sua conta para consultar o catálogo."); return; }
    setCatalogBusy(true);
    try {
      const params = new URLSearchParams({ q: searchTerm, pagina: "1", por_pagina: "30" });
      const response = await fetch(API_URL + "/api/v1/catalogo?" + params, { headers: { Authorization: "Bearer " + token } });
      const body = await response.json();
      if (response.status === 401) { sessionStorage.removeItem("be-token"); setToken(""); setUser(null); throw new Error("Sua sessão expirou. Entre novamente."); }
      if (!response.ok || !body.sucesso) throw new Error(body.erro || "Não foi possível carregar os livros.");
      setBooks(body.dados as Book[]);
      if (!body.dados.length) setNotice("Nenhum livro encontrado para essa pesquisa.");
    } catch (err) { setError(err instanceof Error ? err.message : "Falha de conexão com a API."); }
    finally { setCatalogBusy(false); }
  }

  async function logout() {
    if (token && API_URL) {
      try { await fetch(API_URL + "/api/v1/auth/logout", { method: "POST", headers: { Authorization: "Bearer " + token } }); } catch { /* sessão local será encerrada mesmo sem rede */ }
    }
    sessionStorage.removeItem("be-token"); setToken(""); setUser(null); setBooks([]); setActive("inicio"); setNotice("Você saiu da conta.");
  }

  const activeLabel = nav.flatMap(group => group.items).find(item => item.id === active)?.label ?? "Início";
  return <div className={`app-shell ${collapsed ? "sidebar-collapsed" : ""}`}>
    <aside className="sidebar">
      <div className="brand"><div className="brand-mark">B</div><div className="brand-copy"><strong>Biblioteca</strong><span>ESCOLAR</span></div><button className="collapse-btn" onClick={() => setCollapsed(!collapsed)} aria-label="Recolher menu">‹</button></div>
      <div className="sidebar-label">MENU PRINCIPAL</div>
      {nav.map(group => <section className="nav-group" key={group.group}><div className="group-title">{group.group}</div>{group.items.map(item => <button key={item.id} className={`nav-item ${active === item.id ? "selected" : ""}`} onClick={() => { setActive(item.id); if (item.id === "catalogo" && token) void loadCatalog(); }} title={item.label}><span className="nav-icon">{item.icon}</span><span className="nav-text">{item.label}</span>{item.id === "emprestimos" && <span className="nav-dot" />}</button>)}</section>)}
      <div className="sidebar-bottom"><div className="help-card"><span className="help-icon">?</span><strong>Precisa de ajuda?</strong><small>Acesse o suporte do sistema.</small></div><div className="profile"><div className="avatar">BE</div><div className="profile-copy"><strong>{user?.nome || "Visitante"}</strong><small>{user ? user.tipo : "Não conectado"}</small></div><span className="profile-more">···</span></div></div>
    </aside>
    <main className="main-area">
      <header className="topbar"><div className="mobile-brand"><div className="brand-mark">B</div><strong>Biblioteca Escolar</strong></div><div className="breadcrumbs">Biblioteca <span>/</span> <strong>{activeLabel}</strong></div><div className="top-actions"><label className="appearance-control"><span>Cor</span><select value={appearance} onChange={e => setAppearance(e.target.value as Appearance)} aria-label="Aparência">{appearances.map(a => <option key={a.id} value={a.id}>{a.label}</option>)}</select></label><button className="icon-btn" onClick={() => setTheme(theme === "light" ? "dark" : "light")} aria-label="Alternar tema">{theme === "light" ? "☾" : "☀"}</button>{user ? <button className="user-chip" onClick={() => void logout()}><span className="avatar">BE</span><span className="user-name">{user.nome}</span><span>Sair</span></button> : <button className="user-chip" onClick={() => setActive("login")}>Entrar</button>}</div></header>
      <div className="content">
        <div className="page-heading"><div><div className="eyebrow">BIBLIOTECA ESCOLAR · PAINEL</div><h1>{active === "inicio" ? "Bem-vindo(a)!" : active === "login" ? "Acessar sua conta" : activeLabel}</h1><p>Acompanhe e gerencie as atividades da biblioteca escolar.</p></div><div className="heading-actions"><button className="btn btn-secondary" onClick={() => { setActive("catalogo"); if (token) void loadCatalog(); }}>⌕ <span>Buscar livro</span></button>{user && <button className="btn btn-primary" onClick={() => setActive("emprestimos")}>＋ <span>Novo empréstimo</span></button>}</div></div>
        {!API_URL && <div className="demo-notice"><span>ⓘ</span><span>Frontend preparado, mas a API ainda não foi configurada. Defina VITE_API_URL para habilitar login e catálogo.</span></div>}
        {error && <div className="form-alert error-alert" role="alert">{error}</div>}
        {notice && <div className="form-alert notice-alert" role="status">{notice}</div>}
        {active === "login" && <section className="auth-card"><div><h2>Entrar na Biblioteca</h2><p>Use sua matrícula ou e-mail escolar e sua senha.</p></div><form onSubmit={handleLogin} className="auth-form"><label>Matrícula ou e-mail<input autoComplete="username" value={identifier} onChange={e => setIdentifier(e.target.value)} required placeholder="Digite sua matrícula ou e-mail" /></label><label>Senha<input type="password" autoComplete="current-password" value={password} onChange={e => setPassword(e.target.value)} required placeholder="Digite sua senha" /></label><button className="btn btn-primary" disabled={loginBusy}>{loginBusy ? "Entrando..." : "Entrar"}</button></form></section>}
        {active === "catalogo" && <section className="catalog-card"><div className="catalog-search"><input value={query} onChange={e => setQuery(e.target.value)} onKeyDown={e => { if (e.key === "Enter") void loadCatalog(); }} placeholder="Buscar por título, autor ou ISBN" aria-label="Buscar livros" /><button className="btn btn-primary" onClick={() => void loadCatalog()} disabled={catalogBusy}>{catalogBusy ? "Buscando..." : "Buscar"}</button></div>{books.length > 0 && <div className="book-list">{books.map(book => <article className="book-row" key={book.id}><div className="book-cover">▤</div><div className="book-info"><strong>{book.titulo}</strong><span>{book.autor}</span><small>{book.categoria || "Sem categoria"} · {book.disponiveis} de {book.total_exemplares} disponíveis</small></div><span className={book.disponiveis ? "availability available" : "availability unavailable"}>{book.disponiveis ? "Disponível" : "Indisponível"}</span></article>)}</div>}{!books.length && !catalogBusy && <div className="catalog-empty">Entre na sua conta e faça uma busca para consultar o acervo.</div>}</section>}
        {active !== "login" && active !== "catalogo" && <><section className="stats-grid">
          <article className="stat-card"><div className="stat-top"><span>Empréstimos ativos</span><span className="stat-icon purple">⇄</span></div><strong>—</strong><small>Indicador será integrado à API</small></article>
          <article className="stat-card"><div className="stat-top"><span>Devoluções hoje</span><span className="stat-icon green">↩</span></div><strong>—</strong><small>Indicador será integrado à API</small></article>
          <article className="stat-card"><div className="stat-top"><span>Livros disponíveis</span><span className="stat-icon blue">▤</span></div><strong>—</strong><small>Indicador será integrado à API</small></article>
          <article className="stat-card"><div className="stat-top"><span>Reservas pendentes</span><span className="stat-icon amber">◷</span></div><strong>—</strong><small>Indicador será integrado à API</small></article>
        </section>
        <section className="section-heading"><div><h2>Empréstimos</h2><p>Atalhos para as principais operações da biblioteca.</p></div><button className="text-button" onClick={() => setActive("emprestimos")}>Ver empréstimos <span>→</span></button></section>
        <section className="quick-grid">
          <button className="quick-card" onClick={() => setActive("emprestimos")}><span className="quick-icon purple">＋</span><span className="quick-copy"><strong>Registrar empréstimo</strong><small>Vincule um exemplar a um aluno.</small></span><span className="quick-arrow">→</span></button>
          <button className="quick-card" onClick={() => setActive("devolucoes")}><span className="quick-icon green">↩</span><span className="quick-copy"><strong>Registrar devolução</strong><small>Finalize um empréstimo ativo.</small></span><span className="quick-arrow">→</span></button>
          <button className="quick-card" onClick={() => setActive("reservas")}><span className="quick-icon amber">◷</span><span className="quick-copy"><strong>Gerenciar reservas</strong><small>Consulte a fila de espera.</small></span><span className="quick-arrow">→</span></button>
        </section>
        <section className="section-heading recent-heading"><div><h2>Atividade recente</h2><p>Registros aparecerão aqui após integração.</p></div></section>
        <section className="activity-card"><div className="activity-empty"><div className="empty-icon">▤</div><strong>Nenhuma atividade carregada</strong><span>{user ? "As atividades ainda não estão disponíveis nesta etapa." : "Entre para acessar as funções da biblioteca."}</span></div></section></>}
        <footer className="page-footer"><span>Biblioteca Escolar · Nova interface</span><span>React + TypeScript</span></footer>
      </div>
    </main>
  </div>;
}
