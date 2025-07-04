import { Hono } from 'hono';
import { authenticate } from '../services/authenticator.js';

const app = new Hono<{ Bindings: Bindings }>();

app.post('/authenticate', async (c) => {
  const { username, password, clientToken } = await c.req.json();
  const session = await authenticate(c.env, username, password, clientToken);
  if (!session) {
    const message = !username || !password || !clientToken
      ? { error: 'InvalidRequestException', errorMessage: 'Bad Request.' }
      : { error: 'ForbiddenOperationException', errorMessage: 'Invalid credentials. Invalid username or password.' };
    return c.json(message, message.error === 'InvalidRequestException' ? 400 : 403);
  }
  return c.json(session);
});

export default app;
