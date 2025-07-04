-- Schema setup for Minecraft authserver

-- Users table
create table if not exists users (
  id serial primary key,
  username text not null unique,
  email text not null unique,
  password_hash text not null
);

-- Sessions table
create table if not exists sessions (
  id serial primary key,
  accessToken text not null,
  clientToken text not null,
  accountId integer references users(id) on delete cascade
);

-- Server sessions table
create table if not exists server_sessions (
  id serial primary key,
  accessToken text not null,
  accountId integer references users(id) on delete cascade,
  username text not null,
  selectedProfile text not null,
  serverId text not null
);

-- Storage bucket for skins
insert into storage.buckets (id, name, public)
  values ('skins', 'skins', true)
  on conflict (id) do nothing;
