import "dotenv/config";
import { createHash, randomBytes } from "node:crypto";
import bcrypt from "bcryptjs";
import { rateLimit } from "express-rate-limit";
import express, { type RequestHandler } from "express";
import cors from "cors";
import helmet from "helmet";
import { PrismaClient, type Prisma } from "@prisma/client";
import { z } from "zod";

const app = express();
const prisma = new PrismaClient();
const port = Number(process.env.PORT ?? 3000);

app.disable("x-powered-by");
// Render encaminha as requisições por um proxy reverso.
app.set("trust proxy", 1);
app.use(helmet());
app.use(cors({ origin: process.env.CORS_ORIGIN?.split(",") ?? false }));
app.use(express.json({ limit: "1mb" }));

app.get("/api/v1/health", async (_req, res) => {
  try {
    await prisma.$queryRaw`SELECT 1`;
    res.json({ ok: true, service: "Biblioteca Escolar API", database: "connected" });
  } catch {
    res.status(503).json({ ok: false, service: "Biblioteca Escolar API", database: "unavailable" });
  }
});

const loginSchema = z.object({
  identificador: z.string().trim().min(1).max(150).optional(),
  matricula: z.string().trim().min(1).max(20).optional(),
  email: z.string().trim().email().max(150).optional(),
  senha: z.string().min(1).max(200)
}).refine((data) => Boolean(data.identificador || data.matricula || data.email), {
  message: "Informe e-mail escolar ou matrícula."
});

// O campo legado é DATE. Comparamos a data civil de São Paulo, sem deslocar
// o dia por conversões de fuso horário do servidor.
function alunoEstaSuspenso(suspensoAte: Date | null): boolean {
  if (!suspensoAte) return false;
  const hojeBrasil = new Intl.DateTimeFormat("en-CA", {
    timeZone: "America/Sao_Paulo",
    year: "numeric",
    month: "2-digit",
    day: "2-digit"
  }).format(new Date());
  return suspensoAte.toISOString().slice(0, 10) >= hojeBrasil;
}

// Tokens aleatórios de 48 bytes; somente SHA-256 é persistido no banco.
const loginLimiter = rateLimit({ windowMs: 15 * 60 * 1000, limit: 10, standardHeaders: "draft-8", legacyHeaders: false, message: { sucesso: false, erro: "Muitas tentativas. Aguarde alguns minutos e tente novamente." } });

const requireAuth: RequestHandler = async (req, res, next) => {
  try {
    const authorization = req.header("authorization") ?? "";
    const match = authorization.match(/^Bearer\s+(.+)$/i);
    if (!match) {
      res.status(401).json({ sucesso: false, erro: "Token ausente ou inválido." });
      return;
    }

    const tokenHash = createHash("sha256").update(match[1].trim()).digest("hex");
    const token = await prisma.apiToken.findUnique({
      where: { tokenHash },
      include: { user: true }
    });

    if (
      !token ||
      token.revogado ||
      (token.expiraEm !== null && token.expiraEm <= new Date()) ||
      !token.user.ativo
    ) {
      res.status(401).json({ sucesso: false, erro: "Token ausente, inválido ou expirado." });
      return;
    }

    if (
      token.user.tipo === "ALUNO" &&
      alunoEstaSuspenso(token.user.suspensoAte)
    ) {
      res.status(403).json({ sucesso: false, erro: "Conta suspensa." });
      return;
    }

    res.locals.auth = {
      userId: token.user.id,
      tokenId: token.id,
      tipo: token.user.tipo
    };

    // Atualização de telemetria não deve impedir uma requisição válida.
    void prisma.apiToken.update({
      where: { id: token.id },
      data: { ultimoUso: new Date() }
    }).catch(() => undefined);

    next();
  } catch (error) {
    next(error);
  }
};

app.post("/api/v1/auth/login", loginLimiter, async (req, res, next) => {
  try {
    const parsed = loginSchema.safeParse(req.body);
    if (!parsed.success) return res.status(422).json({ sucesso: false, erro: "Informe e-mail/matrícula e senha válidos." });

    const { senha } = parsed.data;
    const identificador = (parsed.data.identificador ?? parsed.data.matricula ?? parsed.data.email ?? "").trim();
    const user = await prisma.user.findFirst({
      where: {
        ativo: true,
        OR: [
          { matricula: identificador },
          { email: { equals: identificador, mode: "insensitive" } }
        ]
      }
    });
    if (!user) return res.status(401).json({ sucesso: false, erro: "E-mail escolar/matrícula ou senha inválidos." });

    // PHP PASSWORD_DEFAULT normalmente usa bcrypt ($2y$); não redefinimos senhas.
    if (!/^\$2[aby]\$/.test(user.senhaHash)) {
      console.error("Formato de hash legado não suportado para usuário", user.id);
      return res.status(503).json({ sucesso: false, erro: "O formato de senha desta conta ainda precisa ser migrado com segurança." });
    }
    const compatibleHash = user.senhaHash.startsWith("$2y$") ? "$2b$" + user.senhaHash.slice(4) : user.senhaHash;
    if (!(await bcrypt.compare(senha, compatibleHash))) {
      return res.status(401).json({ sucesso: false, erro: "E-mail escolar/matrícula ou senha inválidos." });
    }

    if (user.tipo === "ALUNO" && alunoEstaSuspenso(user.suspensoAte)) {
      return res.status(403).json({ sucesso: false, erro: "Sua conta está suspensa devido a atrasos na devolução." });
    }

    const rawToken = randomBytes(48).toString("hex");
    const tokenHash = createHash("sha256").update(rawToken).digest("hex");
    const expiresAt = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000);
    const token = await prisma.$transaction(async (tx) => {
      const createdToken = await tx.apiToken.create({
        data: { usuarioId: user.id, tokenHash, descricao: "Sessão da API", expiraEm: expiresAt }
      });
      await tx.auditLog.create({
        data: { usuarioId: user.id, acao: "login", entidade: "usuarios", entidadeId: user.id }
      });
      return createdToken;
    });

    return res.json({ sucesso: true, dados: {
      token: rawToken,
      expira_em: token.expiraEm,
      usuario: { id: user.id, matricula: user.matricula, nome: user.nome, email: user.email, turma: user.turma, tipo: user.tipo.toLowerCase() }
    }});
  } catch (error) {
    return next(error);
  }
});

app.get("/api/v1/auth/eu", requireAuth, async (_req, res) => {
  const auth = res.locals.auth as { userId: number; tokenId: number; tipo: string };
  const user = await prisma.user.findUnique({
    where: { id: auth.userId },
    select: {
      id: true,
      matricula: true,
      nome: true,
      email: true,
      turma: true,
      tipo: true,
      suspensoAte: true
    }
  });

  if (!user) {
    return res.status(401).json({ sucesso: false, erro: "Usuário não encontrado." });
  }
  return res.json({ sucesso: true, dados: { ...user, tipo: user.tipo.toLowerCase() } });
});

app.post("/api/v1/auth/logout", requireAuth, async (_req, res) => {
  const auth = res.locals.auth as { userId: number; tokenId: number; tipo: string };
  await prisma.apiToken.update({
    where: { id: auth.tokenId },
    data: { revogado: true }
  });
  return res.json({ sucesso: true, dados: { mensagem: "Token revogado." } });
});

app.get("/api/v1/catalogo", requireAuth, async (req, res) => {
  const q = String(req.query.q ?? "").trim();
  const categoria = String(req.query.categoria ?? "").trim();
  const disponiveis = ["1", "true", "sim"].includes(String(req.query.disponiveis ?? "").toLowerCase());
  const pagina = Math.max(1, Number.parseInt(String(req.query.pagina ?? "1"), 10) || 1);
  const porPagina = Math.min(50, Math.max(1, Number.parseInt(String(req.query.por_pagina ?? "20"), 10) || 20));

  const filters: Prisma.BookWhereInput[] = [];
  if (q) {
    filters.push({
      OR: [
        { titulo: { contains: q, mode: "insensitive" } },
        { autor: { contains: q, mode: "insensitive" } },
        { isbn: { contains: q, mode: "insensitive" } }
      ]
    });
  }
  if (categoria) filters.push({ categoria: { equals: categoria, mode: "insensitive" } });
  if (disponiveis) filters.push({ copies: { some: { status: "DISPONIVEL" } } });

  const where: Prisma.BookWhereInput = { AND: filters };
  const [total, books] = await prisma.$transaction([
    prisma.book.count({ where }),
    prisma.book.findMany({
      where,
      orderBy: { titulo: "asc" },
      skip: (pagina - 1) * porPagina,
      take: porPagina,
      include: {
        _count: { select: { copies: true } },
        copies: { where: { status: "DISPONIVEL" }, select: { id: true } }
      }
    })
  ]);

  return res.json({
    sucesso: true,
    dados: books.map((book) => ({
      id: book.id,
      titulo: book.titulo,
      autor: book.autor,
      isbn: book.isbn,
      editora: book.editora,
      ano: book.ano,
      categoria: book.categoria,
      sinopse: book.sinopse,
      capa: book.capa,
      total_exemplares: book._count.copies,
      disponiveis: book.copies.length
    })),
    meta: {
      pagina,
      por_pagina: porPagina,
      total,
      total_paginas: Math.ceil(total / porPagina)
    }
  });
});

app.get("/api/v1/obras/:id", requireAuth, async (req, res) => {
  const idParam = req.params.id;
  const id = Number.parseInt(typeof idParam === "string" ? idParam : idParam[0] ?? "", 10);
  if (!Number.isSafeInteger(id) || id <= 0) {
    return res.status(422).json({ sucesso: false, erro: "Identificador de obra inválido." });
  }

  const book = await prisma.book.findUnique({
    where: { id },
    include: {
      _count: { select: { copies: true } },
      copies: {
        select: { id: true, tombo: true, status: true, localizacao: true },
        orderBy: { tombo: "asc" }
      }
    }
  });

  if (!book) {
    return res.status(404).json({ sucesso: false, erro: "Obra não encontrada." });
  }

  return res.json({
    sucesso: true,
    dados: {
      id: book.id,
      titulo: book.titulo,
      autor: book.autor,
      isbn: book.isbn,
      editora: book.editora,
      ano: book.ano,
      categoria: book.categoria,
      sinopse: book.sinopse,
      capa: book.capa,
      total_exemplares: book._count.copies,
      exemplares: book.copies
    }
  });
});

app.use((_req, res) => res.status(404).json({ sucesso: false, erro: "Rota não encontrada." }));

app.use((error: unknown, _req: express.Request, res: express.Response, _next: express.NextFunction) => {
  // Não expor detalhes internos ou consultas SQL ao cliente.
  console.error("Erro interno na API", error instanceof Error ? error.message : "unknown");
  res.status(500).json({ sucesso: false, erro: "Erro interno do servidor." });
});

const server = app.listen(port, () => {
  console.log(`Biblioteca Escolar API ouvindo na porta ${port}`);
});

const shutdown = async () => {
  server.close();
  await prisma.$disconnect();
  process.exit(0);
};
process.on("SIGINT", shutdown);
process.on("SIGTERM", shutdown);
