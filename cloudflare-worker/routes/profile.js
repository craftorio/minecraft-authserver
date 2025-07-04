import { getProfile } from '../services/authenticator.js';

export async function actionProfile(id) {
  const data = await getProfile(id);
  if (!data) {
    return new Response('null', { headers: { 'Content-Type': 'application/json' } });
  }
  return new Response(JSON.stringify(data), {
    headers: { 'Content-Type': 'application/json' }
  });
}
