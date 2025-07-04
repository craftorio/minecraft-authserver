import { Hono } from 'hono';
import { getProfile } from '../services/authenticator.js';

const app = new Hono();

app.get('/session/minecraft/profile/:id', async (c) => {
  const id = c.req.param('id');
  const data = await getProfile(id);
  if (!data) {
    return c.json('null');
  }
  return c.json(data);
});

export default app;
