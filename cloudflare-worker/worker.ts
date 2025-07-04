import { Hono } from 'hono';

const app = new Hono<{
  Bindings: {
    SUPABASE_URL: string;
    SUPABASE_KEY: string;
    TEXTURE_PRIVATE_KEY: string;
  };
}>();

const modules = import.meta.glob('./routes/*.js', { eager: true });

for (const path in modules) {
  const mod = modules[path] as any;
  if (typeof mod.default === 'function') {
    app.route('/', mod.default);
  }
}

export default app;
