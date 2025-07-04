declare module 'https://cdn.jsdelivr.net/npm/@supabase/supabase-js/+esm';
declare module 'https://esm.sh/bcryptjs@2.4.3?bundle';

interface ImportMeta {
  glob(pattern: string, options: { eager: true }): Record<string, unknown>;
}

interface Bindings {
  [key: string]: unknown;
  SUPABASE_URL: string;
  SUPABASE_KEY: string;
  TEXTURE_PRIVATE_KEY: string;
}

interface Env {
  Bindings: Bindings;
}
