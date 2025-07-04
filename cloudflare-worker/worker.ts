import { Hono } from 'hono';

const modules = import.meta.glob('./routes/*.js', { eager: true });

function getAction(name: string) {
  const mod = modules[`./routes/${name}.js`];
  const exportName =
    'action' +
    name
      .split('_')
      .map((p) => p.charAt(0).toUpperCase() + p.slice(1))
      .join('');
  return (mod as any)[exportName];
}

const actionHome = getAction('home');
const actionAuthenticate = getAction('authenticate');
const actionRefresh = getAction('refresh');
const actionSessionMinecraftJoin = getAction('session_minecraft_join');
const actionSessionMinecraftHasJoined = getAction('session_minecraft_hasJoined');
const actionSessionMinecraftProfile = getAction('session_minecraft_profile');
const actionTexture = getAction('texture');

const app = new Hono();

app.get('/', (c) => actionHome(c));
app.post('/authenticate', (c) => actionAuthenticate(c));
app.post('/refresh', (c) => actionRefresh(c));
app.post('/session/minecraft/join', (c) => actionSessionMinecraftJoin(c));
app.get('/session/minecraft/hasJoined', (c) => actionSessionMinecraftHasJoined(c));
app.get('/session/minecraft/profile/:id', (c) => actionSessionMinecraftProfile(c));
app.get('/texture/:hash', (c) => actionTexture(c));

export default app;
