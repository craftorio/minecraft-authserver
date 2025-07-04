import { hasJoined } from '../services/authenticator.js';

export async function actionSessionMinecraftHasJoined(url) {
  const serverId = url.searchParams.get('serverId');
  const username = url.searchParams.get('username');
  const data = await hasJoined(serverId, username);
  if (!data) {
    return new Response(null, { status: 401 });
  }
  return new Response(JSON.stringify(data), {
    headers: { 'Content-Type': 'application/json' }
  });
}
