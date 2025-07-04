import { supabase } from '../lib/supabase.js';

export async function actionHasJoined(url) {
  const serverId = url.searchParams.get('serverId');
  const username = url.searchParams.get('username');
  if (!serverId || !username) {
    return new Response(null, { status: 401 });
  }
  const { data: serverSession } = await supabase
    .from('server_sessions')
    .select('*')
    .eq('serverId', serverId)
    .eq('username', username)
    .maybeSingle();
  if (!serverSession) {
    return new Response(null, { status: 401 });
  }
  return new Response(JSON.stringify({
    id: serverSession.selectedProfile,
    name: serverSession.username,
    properties: []
  }), { headers: { 'Content-Type': 'application/json' } });
}
