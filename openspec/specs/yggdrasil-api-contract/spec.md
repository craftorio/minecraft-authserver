# Yggdrasil API Contract

HTTP API behavior for authentication, session, profile, and public key endpoints.

## Requirements

### Requirement: Home endpoint

The server SHALL respond to `GET /` with HTTP 200 and JSON body `null`.

#### Scenario: Health check

- **WHEN** a client sends `GET /` with a non-blocked User-Agent
- **THEN** the response status is 200 and body is `null`

### Requirement: Public keys endpoint

The server SHALL expose RSA public keys at `GET /publickeys` in Yggdrasil format.

#### Scenario: Public keys response shape

- **WHEN** certificates exist and `GET /publickeys` is requested
- **THEN** the response is JSON containing `profilePropertyKeys` array with at least one `publicKey` field

### Requirement: Authentication endpoint

The server SHALL authenticate accounts via `POST /authenticate` with username, password, and clientToken.

#### Scenario: Missing fields rejected

- **WHEN** `POST /authenticate` is sent without required fields
- **THEN** the response status is 400 and body contains `error: ForbiddenOperationException`

#### Scenario: Valid credentials accepted

- **WHEN** `POST /authenticate` is sent with valid username, password, and clientToken for an existing account
- **THEN** the response status is 200 and body contains `accessToken`, `clientToken`, and `selectedProfile` with `id` and `name`

#### Scenario: Invalid credentials rejected

- **WHEN** `POST /authenticate` is sent with wrong password
- **THEN** the response status is 403 and body contains `error: ForbiddenOperationException`

### Requirement: Refresh endpoint

The server SHALL refresh sessions via `POST /refresh` with accessToken and clientToken.

#### Scenario: Valid refresh

- **WHEN** `POST /refresh` is sent with a valid accessToken and matching clientToken
- **THEN** the response status is 200 and body contains new `accessToken` and `selectedProfile`

### Requirement: Session join endpoint

The server SHALL accept `POST /session/minecraft/join` to register a server join.

#### Scenario: Valid join

- **WHEN** `POST /session/minecraft/join` is sent with valid accessToken, selectedProfile, and serverId
- **THEN** the response status is 204

### Requirement: Session hasJoined endpoint

The server SHALL verify server joins via `GET /session/minecraft/hasJoined`.

#### Scenario: Valid hasJoined

- **WHEN** `GET /session/minecraft/hasJoined` is called with correct username and serverId after a join
- **THEN** the response status is 200 and body contains profile with `id` and `name`

### Requirement: Session profile endpoint

The server SHALL return profile data via `GET /session/minecraft/profile/:uuid`.

#### Scenario: Profile lookup

- **WHEN** `GET /session/minecraft/profile/{uuid}` is called for a valid authenticated profile
- **THEN** the response status is 200 and body contains `id`, `name`, and `properties`

### Requirement: Bulk profile lookup

The server SHALL accept bulk name lookups via `POST /profile/lookup/bulk/byname` with a JSON array body.

#### Scenario: Bulk lookup response

- **WHEN** `POST /profile/lookup/bulk/byname` is sent with `["PlayerName"]`
- **THEN** the response is a JSON array with profile entries or empty objects for unknown names
