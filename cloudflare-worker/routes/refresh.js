import { supabase } from '../lib/supabase.js';

export async function actionRefresh(request) {
  const body = await request.json();
  const { accessToken, clientToken } = body;
  if (!accessToken || !clientToken) {
    return new Response(JSON.stringify({
      error: 'InvalidRequestException',
      errorMessage: 'Bad Request.'
    }), { status: 400, headers: { 'Content-Type': 'application/json' } });
  }

  const { data: sessionData, error } = await supabase
    .from('sessions')
    .select('*')
    .eq('accessToken', accessToken)
    .eq('clientToken', clientToken)
    .maybeSingle();
  if (error || !sessionData) {
    return new Response(JSON.stringify({
      error: 'ForbiddenOperationException',
      errorMessage: 'Invalid credentials. Invalid username or password.'
    }), { status: 403, headers: { 'Content-Type': 'application/json' } });
  }

  const { data: account } = await supabase
    .from('users')
    .select('*')
    .eq('id', sessionData.accountId)
    .maybeSingle();
  if (!account) {
    return new Response(JSON.stringify({
      error: 'ForbiddenOperationException',
      errorMessage: 'Invalid credentials. Invalid username or password.'
    }), { status: 403, headers: { 'Content-Type': 'application/json' } });
  }

  const newSession = {
    accessToken: crypto.randomUUID(),
    clientToken,
    accountId: account.id
  };
  await supabase.from('sessions').update(newSession).eq('id', sessionData.id);

  return new Response(JSON.stringify(newSession), {
    headers: { 'Content-Type': 'application/json' }
  });
}
