// Cloudflare Worker entry point that mimics the PHP routes
import { createClient } from 'https://cdn.jsdelivr.net/npm/@supabase/supabase-js/+esm';
import bcrypt from 'https://esm.sh/bcryptjs@2.4.3?bundle';

const SUPABASE_URL = globalThis.SUPABASE_URL;
const SUPABASE_KEY = globalThis.SUPABASE_KEY;
const supabase = createClient(SUPABASE_URL, SUPABASE_KEY);

function home() {
  return new Response('null', { headers: { 'Content-Type': 'application/json' } });
}

async function authenticate(request) {
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

async function refresh(request) {
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

async function texture(hash) {
  const { data, error } = await supabase
    .storage
    .from('skins')
    .download(`${hash}.png`);
  if (error || !data) {
    return new Response('Not Found', { status: 404 });
  }
  return new Response(data, {
    headers: { 'Content-Type': 'image/png' }
  });
}

async function joinServer(request) {
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

async function hasJoined(url) {
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

async function profile(id) {
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

export default {
  async fetch(request) {
    const url = new URL(request.url);
    if (request.method === 'POST' && url.pathname === '/authenticate') {
      return authenticate(request);
    }
    if (request.method === 'POST' && url.pathname === '/refresh') {
      return refresh(request);
    }
    if (request.method === 'POST' && url.pathname === '/session/minecraft/join') {
      return joinServer(request);
    }
    if (request.method === 'GET' && url.pathname === '/session/minecraft/hasJoined') {
      return hasJoined(url);
    }
    if (request.method === 'GET' && url.pathname.startsWith('/session/minecraft/profile/')) {
      const profileId = url.pathname.split('/').pop();
      return profile(profileId);
    }
    if (request.method === 'GET' && url.pathname.startsWith('/texture/')) {
      const hash = url.pathname.split('/').pop();
      return texture(hash);
    }
    if (request.method === 'GET' && url.pathname === '/') {
      return home();
    }
    return new Response('Not Found', { status: 404 });
  }
};
