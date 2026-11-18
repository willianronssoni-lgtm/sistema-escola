import "dotenv/config";
import express from "express";
import cors from "cors";
import helmet from "helmet";
import { PrismaClient } from "@prisma/client";
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
  matricula: z.string().trim().min(1).max(20),
  senha: z.string().min(1).max(200)
});

app.post("/api/v1/auth/login", (_req, res) => {
  const parsed = loginSchema.safeParse(_req.body);
  if (!parsed.success) {
    return res.status(422).json({ ok: false, error: "Informe matrícula e senha válidas." });
  }
  // TODO(migration): implementar autenticação após migrar/verificar os hashes de senha.
  return res.status(501).json({ ok: false, error: "Autenticação ainda não migrada." });
});

app.use((_req, res) => res.status(404).json({ ok: false, error: "Rota não encontrada." }));

app.listen(port, () => {
  console.log(`Biblioteca Escolar API ouvindo na porta ${port}`);
});

process.on("SIGINT", async () => { await prisma.$disconnect(); process.exit(0); });
process.on("SIGTERM", async () => { await prisma.$disconnect(); process.exit(0); });
