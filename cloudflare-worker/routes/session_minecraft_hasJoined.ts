import { Hono } from 'hono';
import { hasJoined } from '../services/authenticator.js';

const app = new Hono<{ Bindings: Bindings }>();

app.get('/session/minecraft/hasJoined', async (c) => {
  const serverId = c.req.query('serverId');
  const username = c.req.query('username');
  const data = await hasJoined(c.env, serverId, username);
  if (!data) {
    return c.body(null, 401);
  }
  return c.json(data);
});

export default app;
