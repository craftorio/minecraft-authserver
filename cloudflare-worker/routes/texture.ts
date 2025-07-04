import { Hono } from 'hono';
import { getTexture } from '../services/authenticator.js';

const app = new Hono<{ Bindings: Bindings }>();

app.get('/texture/:hash', async (c) => {
  const hash = c.req.param('hash');
  const data = await getTexture(c.env, hash);
  if (!data) {
    return new Response('Not Found', { status: 404 });
  }
  return new Response(data, { headers: { 'Content-Type': 'image/png' } });
});

export default app;
