# Build stage
FROM node:20 AS build
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY tsconfig.json ./
COPY global.d.ts ./
COPY cloudflare-worker ./cloudflare-worker
RUN npm run build

# Runtime stage
FROM node:20-alpine
WORKDIR /app
COPY --from=build /app /app
RUN npm install -g wrangler
EXPOSE 8787
CMD ["wrangler", "dev", "dist/worker.js", "--host", "0.0.0.0", "--port", "8787"]
