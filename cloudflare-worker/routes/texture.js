import { getTexture } from '../services/authenticator.js';

export async function actionTexture(hash) {
  const data = await getTexture(hash);
  if (!data) {
    return new Response('Not Found', { status: 404 });
  }
  return new Response(data, {
    headers: { 'Content-Type': 'image/png' }
  });
}
