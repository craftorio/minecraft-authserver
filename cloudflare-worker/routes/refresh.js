import { refresh } from '../services/authenticator.js';

export async function actionRefresh(request) {
  const body = await request.json();
  const { accessToken, clientToken } = body;
  const session = await refresh(accessToken, clientToken);
  if (!session) {
    const message = !accessToken || !clientToken
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
