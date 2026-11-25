# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Imagen del frontend: React + Vite + TypeScript en modo desarrollo
# ---------------------------------------------------------------------------
FROM node:24-bookworm-slim

WORKDIR /app

# Cachea dependencias
COPY package*.json ./
RUN npm install

COPY . .

EXPOSE 5173

CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0", "--port", "5173"]
