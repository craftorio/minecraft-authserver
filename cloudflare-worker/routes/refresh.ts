import type { Context } from 'hono';
import { refresh } from '../services/authenticator.js';

export async function actionRefresh(c: Context) {
  const { accessToken, clientToken } = await c.req.json();
  const session = await refresh(accessToken, clientToken);
  if (!session) {
    const message = !accessToken || !clientToken
      ? { error: 'InvalidRequestException', errorMessage: 'Bad Request.' }
      : { error: 'ForbiddenOperationException', errorMessage: 'Invalid credentials. Invalid username or password.' };
    return c.json(message, message.error === 'InvalidRequestException' ? 400 : 403);
  }
  return c.json(session);
}
