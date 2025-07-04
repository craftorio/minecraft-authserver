// Cloudflare Worker entry point that uses separate action handlers
import { actionHome } from './routes/home.js';
import { actionAuthenticate } from './routes/authenticate.js';
import { actionRefresh } from './routes/refresh.js';
import { actionSessionMinecraftJoin } from './routes/session_minecraft_join.js';
import { actionSessionMinecraftHasJoined } from './routes/session_minecraft_hasJoined.js';
import { actionSessionMinecraftProfile } from './routes/session_minecraft_profile.js';
import { actionTexture } from './routes/texture.js';

export default {
  async fetch(request) {
    const url = new URL(request.url);
    if (request.method === 'POST' && url.pathname === '/authenticate') {
      return actionAuthenticate(request);
    }
    if (request.method === 'POST' && url.pathname === '/refresh') {
      return actionRefresh(request);
    }
    if (request.method === 'POST' && url.pathname === '/session/minecraft/join') {
      return actionSessionMinecraftJoin(request);
    }
    if (request.method === 'GET' && url.pathname === '/session/minecraft/hasJoined') {
      return actionSessionMinecraftHasJoined(url);
    }
    if (request.method === 'GET' && url.pathname.startsWith('/session/minecraft/profile/')) {
      const profileId = url.pathname.split('/').pop();
      return actionSessionMinecraftProfile(profileId);
    }
    if (request.method === 'GET' && url.pathname.startsWith('/texture/')) {
      const hash = url.pathname.split('/').pop();
      return actionTexture(hash);
    }
    if (request.method === 'GET' && url.pathname === '/') {
      return actionHome();
    }
    return new Response('Not Found', { status: 404 });
  }
};
