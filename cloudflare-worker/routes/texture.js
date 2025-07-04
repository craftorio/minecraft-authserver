import { supabase } from '../lib/supabase.js';

export async function actionTexture(hash) {
  const { data, error } = await supabase
    .storage
    .from('skins')
    .download(`${hash}.png`);
  if (error || !data) {
    return new Response('Not Found', { status: 404 });
  }
  return new Response(data, {
    headers: { 'Content-Type': 'image/png' }
  });
}
