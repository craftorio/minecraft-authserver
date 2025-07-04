import { createClient } from '@supabase/supabase-js';

const SUPABASE_URL = globalThis.SUPABASE_URL;
const SUPABASE_KEY = globalThis.SUPABASE_KEY;

export const supabase = createClient(SUPABASE_URL, SUPABASE_KEY);
