import type { Context } from 'hono';

export function actionHome(c: Context) {
  return c.json('null');
}
