import type { Context } from 'hono';
import { getProfile } from '../services/authenticator.js';

export async function actionSessionMinecraftProfile(c: Context) {
  const id = c.req.param('id');
  const data = await getProfile(id);
  if (!data) {
    return c.json('null');
  }
  return c.json(data);
}
