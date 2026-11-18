import { useEffect, useState } from "react";

type Appearance = "roxo" | "azul" | "branco" | "dourado" | "laranja" | "bordo" | "esmeralda";
type Theme = "light" | "dark";
const appearances: { id: Appearance; label: string; color: string }[] = [
  { id: "roxo", label: "Roxo discreto", color: "#7562a8" },
  { id: "azul", label: "Azul", color: "#3975c6" },
  { id: "branco", label: "Prata claro", color: "#8793a3" },
  { id: "dourado", label: "Dourado", color: "#ad8738" },
  { id: "laranja", label: "Laranja", color: "#d97736" },
  { id: "bordo", label: "Bordô", color: "#873e55" },
  { id: "esmeralda", label: "Verde esmeralda", color: "#25836b" }
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
  const [search, setSearch] = useState("");

  useEffect(() => {
    document.documentElement.dataset.theme = theme;
    document.documentElement.dataset.appearance = appearance;
    localStorage.setItem("be-theme", theme);
    localStorage.setItem("be-appearance", appearance);
  }, [theme, appearance]);

  const activeLabel = nav.flatMap(group => group.items).find(item => item.id === active)?.label ?? "Início";
  return <div className={`app-shell ${collapsed ? "sidebar-collapsed" : ""}`}>
    <aside className="sidebar">
      <div className="brand"><div className="brand-mark">B</div><div className="brand-copy"><strong>Biblioteca</strong><span>ESCOLAR</span></div><button className="collapse-btn" onClick={() => setCollapsed(!collapsed)} aria-label="Recolher menu">‹</button></div>
      <div className="sidebar-label">MENU PRINCIPAL</div>
      {nav.map(group => <section className="nav-group" key={group.group}><div className="group-title">{group.group}</div>{group.items.map(item => <button key={item.id} className={`nav-item ${active === item.id ? "selected" : ""}`} onClick={() => setActive(item.id)} title={item.label}><span className="nav-icon">{item.icon}</span><span className="nav-text">{item.label}</span>{item.id === "emprestimos" && <span className="nav-dot" />}</button>)}</section>)}
      <div className="sidebar-bottom"><div className="help-card"><span className="help-icon">?</span><strong>Precisa de ajuda?</strong><small>Acesse o suporte do sistema.</small></div><div className="profile"><div className="avatar">BE</div><div className="profile-copy"><strong>Bibliotecário</strong><small>Ambiente de demonstração</small></div><span className="profile-more">···</span></div></div>
    </aside>
    <main className="main-area">
      <header className="topbar"><div className="mobile-brand"><div className="brand-mark">B</div><strong>Biblioteca Escolar</strong></div><div className="breadcrumbs">Biblioteca <span>/</span> <strong>{activeLabel}</strong></div><div className="top-actions"><label className="appearance-control"><span>Cor</span><select value={appearance} onChange={e => setAppearance(e.target.value as Appearance)} aria-label="Aparência">{appearances.map(a => <option key={a.id} value={a.id}>{a.label}</option>)}</select></label><button className="icon-btn" onClick={() => setTheme(theme === "light" ? "dark" : "light")} aria-label="Alternar tema">{theme === "light" ? "☾" : "☀"}</button><button className="user-chip"><span className="avatar">BE</span><span className="user-name">Bibliotecário</span><span>⌄</span></button></div></header>
      <div className="content">
        <div className="page-heading"><div><div className="eyebrow">QUARTA-FEIRA · PAINEL DA BIBLIOTECA</div><h1>{active === "inicio" ? "Bom dia! 👋" : activeLabel}</h1><p>Acompanhe e gerencie as atividades da biblioteca escolar.</p></div><div className="heading-actions"><button className="btn btn-secondary" onClick={() => setActive("catalogo")}>⌕ <span>Buscar livro</span></button><button className="btn btn-primary" onClick={() => setActive("emprestimos")}>＋ <span>Novo empréstimo</span></button></div></div>
        <div className="demo-notice"><span>ⓘ</span><span>Prévia da nova interface — dados demonstrativos. A conexão com o banco e as operações reais ainda serão integradas.</span></div>
        <section className="stats-grid">
          <article className="stat-card"><div className="stat-top"><span>Empréstimos ativos</span><span className="stat-icon purple">⇄</span></div><strong>—</strong><small>Dados serão carregados da API</small></article>
          <article className="stat-card"><div className="stat-top"><span>Devoluções hoje</span><span className="stat-icon green">↩</span></div><strong>—</strong><small>Dados serão carregados da API</small></article>
          <article className="stat-card"><div className="stat-top"><span>Livros disponíveis</span><span className="stat-icon blue">▤</span></div><strong>—</strong><small>Dados serão carregados da API</small></article>
          <article className="stat-card"><div className="stat-top"><span>Reservas pendentes</span><span className="stat-icon amber">◷</span></div><strong>—</strong><small>Dados serão carregados da API</small></article>
        </section>
        <section className="section-heading"><div><h2>Empréstimos</h2><p>Atalhos para as principais operações da biblioteca.</p></div><button className="text-button" onClick={() => setActive("emprestimos")}>Ver empréstimos <span>→</span></button></section>
        <section className="quick-grid">
          <button className="quick-card" onClick={() => setActive("emprestimos")}><span className="quick-icon purple">＋</span><span className="quick-copy"><strong>Registrar empréstimo</strong><small>Vincule um exemplar a um aluno.</small></span><span className="quick-arrow">→</span></button>
          <button className="quick-card" onClick={() => setActive("devolucoes")}><span className="quick-icon green">↩</span><span className="quick-copy"><strong>Registrar devolução</strong><small>Finalize um empréstimo ativo.</small></span><span className="quick-arrow">→</span></button>
          <button className="quick-card" onClick={() => setActive("reservas")}><span className="quick-icon amber">◷</span><span className="quick-copy"><strong>Gerenciar reservas</strong><small>Consulte a fila de espera.</small></span><span className="quick-arrow">→</span></button>
        </section>
        <section className="section-heading recent-heading"><div><h2>Atividade recente</h2><p>Os registros aparecerão aqui quando a API estiver integrada.</p></div></section>
        <section className="activity-card"><div className="activity-empty"><div className="empty-icon">▤</div><strong>Nenhuma atividade carregada</strong><span>Esta prévia ainda não está conectada ao banco de dados.</span></div></section>
        <footer className="page-footer"><span>Biblioteca Escolar · Nova interface</span><span>React + TypeScript</span></footer>
      </div>
    </main>
  </div>;
}