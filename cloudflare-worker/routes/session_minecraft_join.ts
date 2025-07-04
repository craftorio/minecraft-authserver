import { Hono } from 'hono';
import { joinServer } from '../services/authenticator.js';

const app = new Hono<{ Bindings: Bindings }>();

app.post('/session/minecraft/join', async (c) => {
  try {
    const { accessToken, selectedProfile, serverId } = await c.req.json();
    const success = await joinServer(c.env, accessToken, selectedProfile, serverId);
    if (!success) {
      return c.body(null, 401);
    }
    return c.body(null, 204);
  } catch {
    return c.body(null, 500);
  }
});

export default app;
