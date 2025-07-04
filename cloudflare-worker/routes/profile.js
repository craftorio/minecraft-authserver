import { supabase } from '../lib/supabase.js';

export async function actionProfile(id) {
  const { data: serverSession } = await supabase
    .from('server_sessions')
    .select('*')
    .eq('selectedProfile', id)
    .maybeSingle();
  if (!serverSession) {
    return new Response('null', { headers: { 'Content-Type': 'application/json' } });
  }
  return new Response(JSON.stringify({
    id,
    name: serverSession.username,
    properties: []
  }), { headers: { 'Content-Type': 'application/json' } });
}
