import bcrypt from 'bcryptjs';
import { getSupabase, SupabaseEnv } from '../lib/supabase.js';

export interface WorkerEnv extends Bindings {}

export interface Session {
  accessToken: string;
  clientToken: string;
  accountId: number;
}

export async function authenticate(env: WorkerEnv, username: string, password: string, clientToken: string): Promise<Session | null> {
  const supabase = getSupabase(env);
  if (!username || !password || !clientToken) {
    return null;
  }
  const { data: account, error } = await supabase
    .from('users')
    .select('*')
    .or(`username.eq.${username},email.eq.${username}`)
    .maybeSingle();
  if (error || !account) {
    return null;
  }
  const valid = await bcrypt.compare(password, account.password_hash);
  if (!valid) {
    return null;
  }
  const session: Session = {
    accessToken: crypto.randomUUID(),
    clientToken,
    accountId: account.id
  };
  await supabase.from('sessions').insert(session);
  return session;
}

export async function refresh(env: WorkerEnv, accessToken: string, clientToken: string): Promise<Session | null> {
  const supabase = getSupabase(env);
  if (!accessToken || !clientToken) {
    return null;
  }
  const { data: sessionData } = await supabase
    .from('sessions')
    .select('*')
    .eq('accessToken', accessToken)
    .eq('clientToken', clientToken)
    .maybeSingle();
  if (!sessionData) {
    return null;
  }
  const { data: account } = await supabase
    .from('users')
    .select('*')
    .eq('id', sessionData.accountId)
    .maybeSingle();
  if (!account) {
    return null;
  }
  const newSession: Session = {
    accessToken: crypto.randomUUID(),
    clientToken,
    accountId: account.id
  };
  await supabase.from('sessions').update(newSession).eq('id', sessionData.id);
  return newSession;
}

export async function joinServer(env: WorkerEnv, accessToken: string, selectedProfile: string, serverId: string): Promise<boolean> {
  const supabase = getSupabase(env);
  if (!accessToken || !selectedProfile || !serverId) {
    return false;
  }
  const { data: session } = await supabase
    .from('sessions')
    .select('*')
    .eq('accessToken', accessToken)
    .maybeSingle();
  if (!session) {
    return false;
  }
  const { data: account } = await supabase
    .from('users')
    .select('*')
    .eq('id', session.accountId)
    .maybeSingle();
  if (!account) {
    return false;
  }
  await supabase.from('server_sessions').upsert({
    accessToken,
    accountId: account.id,
    username: account.username,
    selectedProfile,
    serverId
  });
  return true;
}

export async function hasJoined(env: WorkerEnv, serverId: string, username: string) {
  const supabase = getSupabase(env);
  if (!serverId || !username) {
    return null;
  }
  const { data: serverSession } = await supabase
    .from('server_sessions')
    .select('*')
    .eq('serverId', serverId)
    .eq('username', username)
    .maybeSingle();
  if (!serverSession) {
    return null;
  }
  const props = await buildProperties(
    serverSession.username,
    serverSession.selectedProfile,
    serverSession.username,
    env
  );
  return {
    id: serverSession.selectedProfile,
    name: serverSession.username,
    properties: props
  };
}

export async function getProfile(env: WorkerEnv, id: string) {
  const supabase = getSupabase(env);
  const { data: serverSession } = await supabase
    .from('server_sessions')
    .select('*')
    .eq('selectedProfile', id)
    .maybeSingle();
  if (!serverSession) {
    return null;
  }
  const props = await buildProperties(
    serverSession.username,
    id,
    serverSession.username,
    env
  );
  return {
    id,
    name: serverSession.username,
    properties: props
  };
}

export async function getTexture(env: WorkerEnv, hash: string) {
  const supabase = getSupabase(env);
  const { data } = await supabase.storage.from('skins').download(`${hash}.png`);
  if (!data) {
    return null;
  }
  return data;
}

async function buildProperties(username: string, profileId: string, profileName: string, env: WorkerEnv) {
  const encoder = new TextEncoder();
  const digest = await crypto.subtle.digest('SHA-256', encoder.encode(username));
  const hash = Array.from(new Uint8Array(digest))
    .map((b) => b.toString(16).padStart(2, '0'))
    .join('');
  const textures = {
    timestamp: Date.now(),
    profileId,
    profileName,
    textures: {
      SKIN: {
        url: `https://textures.minecraft.net/texture/${hash}`
      }
    }
  };
  const value = btoa(JSON.stringify(textures));
  const signature = await signValue(value, env.TEXTURE_PRIVATE_KEY);
  return [
    {
      name: 'textures',
      value,
      signature
    }
  ];
}

function pemToArrayBuffer(pem: string): ArrayBuffer {
  const b64 = pem
    .replace(/-----BEGIN[^-]+-----/, '')
    .replace(/-----END[^-]+-----/, '')
    .replace(/\s+/g, '');
  const binary = atob(b64);
  const bytes = new Uint8Array(binary.length);
  for (let i = 0; i < binary.length; i++) {
    bytes[i] = binary.charCodeAt(i);
  }
  return bytes.buffer;
}

async function signValue(value: string, privatePem: string): Promise<string> {
  if (!privatePem) {
    return '';
  }
  const key = await crypto.subtle.importKey(
    'pkcs8',
    pemToArrayBuffer(privatePem),
    { name: 'RSASSA-PKCS1-v1_5', hash: 'SHA-1' },
    false,
    ['sign']
  );
  const signature = await crypto.subtle.sign(
    'RSASSA-PKCS1-v1_5',
    key,
    new TextEncoder().encode(value)
  );
  return btoa(String.fromCharCode(...new Uint8Array(signature)));
}
