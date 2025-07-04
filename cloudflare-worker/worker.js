// Cloudflare Worker entry point that uses separate action handlers
import { actionHome } from './routes/home.js';
import { actionAuthenticate } from './routes/authenticate.js';
import { actionRefresh } from './routes/refresh.js';
import { actionJoinServer } from './routes/joinServer.js';
import { actionHasJoined } from './routes/hasJoined.js';
import { actionProfile } from './routes/profile.js';
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
      return actionJoinServer(request);
    }
    if (request.method === 'GET' && url.pathname === '/session/minecraft/hasJoined') {
      return actionHasJoined(url);
    }
    if (request.method === 'GET' && url.pathname.startsWith('/session/minecraft/profile/')) {
      const profileId = url.pathname.split('/').pop();
      return actionProfile(profileId);
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
