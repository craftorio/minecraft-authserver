import bcrypt from 'https://esm.sh/bcryptjs@2.4.3?bundle';
import { supabase } from '../lib/supabase.js';

export async function authenticate(username, password, clientToken) {
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
  const session = {
    accessToken: crypto.randomUUID(),
    clientToken,
    accountId: account.id
  };
  await supabase.from('sessions').insert(session);
  return session;
}

export async function refresh(accessToken, clientToken) {
  if (!accessToken || !clientToken) {
    return null;
  }
  const { data: sessionData, error } = await supabase
    .from('sessions')
    .select('*')
    .eq('accessToken', accessToken)
    .eq('clientToken', clientToken)
    .maybeSingle();
  if (error || !sessionData) {
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
  const newSession = {
    accessToken: crypto.randomUUID(),
    clientToken,
    accountId: account.id
  };
  await supabase.from('sessions').update(newSession).eq('id', sessionData.id);
  return newSession;
}

export async function joinServer(accessToken, selectedProfile, serverId) {
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

export async function hasJoined(serverId, username) {
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
    serverSession.username
  );
  return {
    id: serverSession.selectedProfile,
    name: serverSession.username,
    properties: props
  };
}

export async function getProfile(id) {
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
    serverSession.username
  );
  return {
    id,
    name: serverSession.username,
    properties: props
  };
}

export async function getTexture(hash) {
  const { data, error } = await supabase
    .storage
    .from('skins')
    .download(`${hash}.png`);
  if (error || !data) {
    return null;
  }
  return data;
}

async function buildProperties(username, profileId, profileName) {
  const encoder = new TextEncoder();
  const digest = await crypto.subtle.digest(
    'SHA-256',
    encoder.encode(username)
  );
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
  return [
    {
      name: 'textures',
      value,
      signature: ''
    }
  ];
}
