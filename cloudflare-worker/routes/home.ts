import { Hono } from 'hono';

const app = new Hono();

app.get('/', (c) => c.json('null'));

export default app;
