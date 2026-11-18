import "dotenv/config";
import { createHash } from "node:crypto";
import express, { type RequestHandler } from "express";
import cors from "cors";
import helmet from "helmet";
import { PrismaClient, type Prisma } from "@prisma/client";
import { z } from "zod";

const app = express();
const prisma = new PrismaClient();
const port = Number(process.env.PORT ?? 3000);

app.disable("x-powered-by");
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
  matricula: z.string().trim().min(1).max(150),
  senha: z.string().min(1).max(200)
});

// A validação do token já segue o formato legado: o banco guarda somente SHA-256.
// O login permanece fechado até confirmar compatibilidade dos hashes PHP existentes.
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
      token.user.suspensoAte !== null &&
      token.user.suspensoAte >= new Date(new Date().toISOString().slice(0, 10))
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

app.post("/api/v1/auth/login", (_req, res) => {
  const parsed = loginSchema.safeParse(_req.body);
  if (!parsed.success) {
    return res.status(422).json({ sucesso: false, erro: "Informe e-mail/matrícula e senha válidos." });
  }
  return res.status(501).json({
    sucesso: false,
    erro: "Login em migração: a compatibilidade dos hashes de senha ainda precisa ser validada."
  });
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
  return res.json({ sucesso: true, dados: user });
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
  const id = Number.parseInt(req.params.id, 10);
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
