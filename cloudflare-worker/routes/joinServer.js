import { supabase } from '../lib/supabase.js';

export async function actionJoinServer(request) {
  try {
    const { accessToken, selectedProfile, serverId } = await request.json();
    if (!accessToken || !selectedProfile || !serverId) {
      return new Response(null, { status: 401 });
    }
    const { data: session } = await supabase
      .from('sessions')
      .select('*')
      .eq('accessToken', accessToken)
      .maybeSingle();
    if (!session) {
      return new Response(null, { status: 401 });
    }
    const { data: account } = await supabase
      .from('users')
      .select('*')
      .eq('id', session.accountId)
      .maybeSingle();
    if (!account) {
      return new Response(null, { status: 401 });
    }
    await supabase.from('server_sessions').upsert({
      accessToken,
      accountId: account.id,
      username: account.username,
      selectedProfile,
      serverId
    });
    return new Response(null, { status: 204 });
  } catch {
    return new Response(null, { status: 500 });
  }
}
