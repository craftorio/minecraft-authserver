import { joinServer } from '../services/authenticator.js';
export async function actionSessionMinecraftJoin(c) {
    try {
        const { accessToken, selectedProfile, serverId } = await c.req.json();
        const success = await joinServer(accessToken, selectedProfile, serverId);
        if (!success) {
            return c.body(null, 401);
        }
        return c.body(null, 204);
    }
    catch {
        return c.body(null, 500);
    }
}
