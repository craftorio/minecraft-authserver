import type { Context } from 'hono';
import { hasJoined } from '../services/authenticator.js';

export async function actionSessionMinecraftHasJoined(c: Context) {
  const serverId = c.req.query('serverId');
  const username = c.req.query('username');
  const data = await hasJoined(serverId, username);
  if (!data) {
    return c.body(null, 401);
  }
  return c.json(data);
}
