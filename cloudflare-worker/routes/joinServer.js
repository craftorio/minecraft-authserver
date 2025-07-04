import { joinServer } from '../services/authenticator.js';

export async function actionJoinServer(request) {
  try {
    const { accessToken, selectedProfile, serverId } = await request.json();
    const success = await joinServer(accessToken, selectedProfile, serverId);
    if (!success) {
      return new Response(null, { status: 401 });
    }
    return new Response(null, { status: 204 });
  } catch {
    return new Response(null, { status: 500 });
  }
}
