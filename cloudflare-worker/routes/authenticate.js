import { authenticate } from '../services/authenticator.js';

export async function actionAuthenticate(request) {
  const body = await request.json();
  const { username, password, clientToken } = body;
  const session = await authenticate(username, password, clientToken);
  if (!session) {
    const message = !username || !password || !clientToken
      ? { error: 'InvalidRequestException', errorMessage: 'Bad Request.' }
      : { error: 'ForbiddenOperationException', errorMessage: 'Invalid credentials. Invalid username or password.' };
    return new Response(JSON.stringify(message), {
      status: message.error === 'InvalidRequestException' ? 400 : 403,
      headers: { 'Content-Type': 'application/json' }
    });
  }
  return new Response(JSON.stringify(session), {
    headers: { 'Content-Type': 'application/json' }
  });
}
