import { createClient, SupabaseClient } from '@supabase/supabase-js';

export interface SupabaseEnv {
  SUPABASE_URL: string;
  SUPABASE_KEY: string;
}

export function getSupabase(env: SupabaseEnv): SupabaseClient {
  return createClient(env.SUPABASE_URL, env.SUPABASE_KEY);
}
