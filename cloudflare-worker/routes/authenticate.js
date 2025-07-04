import bcrypt from 'https://esm.sh/bcryptjs@2.4.3?bundle';
import { supabase } from '../lib/supabase.js';

export async function actionAuthenticate(request) {
  const body = await request.json();
  const { username, password, clientToken } = body;
  if (!username || !password || !clientToken) {
    return new Response(JSON.stringify({
      error: 'InvalidRequestException',
      errorMessage: 'Bad Request.'
    }), { status: 400, headers: { 'Content-Type': 'application/json' } });
  }

  const { data: account, error } = await supabase
    .from('users')
    .select('*')
    .or(`username.eq.${username},email.eq.${username}`)
    .maybeSingle();
  if (error || !account) {
    return new Response(JSON.stringify({
      error: 'ForbiddenOperationException',
      errorMessage: 'Invalid credentials. Invalid username or password.'
    }), { status: 403, headers: { 'Content-Type': 'application/json' } });
  }

  const valid = await bcrypt.compare(password, account.password_hash);
  if (!valid) {
    return new Response(JSON.stringify({
      error: 'ForbiddenOperationException',
      errorMessage: 'Invalid credentials. Invalid username or password.'
    }), { status: 403, headers: { 'Content-Type': 'application/json' } });
  }

  const session = {
    accessToken: crypto.randomUUID(),
    clientToken,
    accountId: account.id
  };
  await supabase.from('sessions').insert(session);

  return new Response(JSON.stringify(session), {
    headers: { 'Content-Type': 'application/json' }
  });
}
