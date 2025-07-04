import { Hono } from 'hono';

const app = new Hono<{
  Bindings: {
    SUPABASE_URL: string;
    SUPABASE_KEY: string;
    TEXTURE_PRIVATE_KEY: string;
  };
}>();

import auth from './routes/authenticate.js';
import refresh from './routes/refresh.js';
import join from './routes/session_minecraft_join.js';
import hasJoined from './routes/session_minecraft_hasJoined.js';
import profile from './routes/session_minecraft_profile.js';
import texture from './routes/texture.js';
import home from './routes/home.js';

const fallback = {
  './routes/authenticate.js': { default: auth },
  './routes/refresh.js': { default: refresh },
  './routes/session_minecraft_join.js': { default: join },
  './routes/session_minecraft_hasJoined.js': { default: hasJoined },
  './routes/session_minecraft_profile.js': { default: profile },
  './routes/texture.js': { default: texture },
  './routes/home.js': { default: home }
};

const modules = (import.meta as any).glob?.('./routes/*.js', { eager: true }) || fallback;

for (const path in modules) {
  const mod = modules[path] as any;
  if (typeof mod.default === 'function') {
    app.route('/', mod.default);
  }
}

export default app;
