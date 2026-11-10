# Vuetiful Crypty Notes App

A single-page application (SPA) for creating, editing, and managing notes with optional file attachments and **on-chain blockchain notarization (Proof of Existence & tamper detection)**. Built with Laravel 13, Vue 3, Pinia, Viem, Solidity (Foundry), and Tailwind CSS v4, running locally via Laravel Sail and Anvil.

## Tech Stack

- **Backend:** PHP 8.3+, Laravel 13
- **Frontend:** Vue 3 (`<script setup>`), Pinia 4, Axios, Viem (`viem`)
- **Blockchain & Smart Contracts:** Solidity (`^0.8.24`), Foundry (`forge`, `anvil`, `cast`), MetaMask
- **Styling & Build:** Tailwind CSS v4 (`@tailwindcss/vite`), Vite 8
- **API Documentation:** Scramble (`dedoc/scramble`) — OpenAPI 3.1.0 & Stoplight Elements UI
- **Local Environment:** Laravel Sail (Docker) + Foundry Anvil (`localhost:8545`)

## Features

- Create, view, update, and delete notes
- Attach files to notes (stored on Laravel's `public` disk and cleaned up automatically on replacement or deletion)
- **On-Chain Note & Attachment Notarization:**
  - Deterministic SHA-256 `bytes32` fingerprinting of `title + content + attachment_bytes`
  - Anchors note hashes on-chain via the `NoteNotary.sol` smart contract and MetaMask
  - Backend JSON-RPC verification (`eth_call` against `NoteNotary.verify(bytes32)`) before persisting notarization metadata
  - **Live Tamper Detection:** Automatically compares a note's current content/attachment hash against its notarized on-chain hash (`verified`, `tampered`, or `unnotarized`)
- Reactive state management via Pinia
- Automatic OpenAPI documentation generated from routes and controller validation rules

## Getting Started (Laravel Sail + Local Blockchain)

### Prerequisites

- Docker Desktop (or Docker Engine + Docker Compose)
- Node.js & npm (optional on host if running everything via Sail)
- [Foundry](https://book.getfoundry.sh/getting-started/installation) (`forge`, `anvil`, `cast`) installed on host for local blockchain development
- [MetaMask](https://metamask.io/) browser extension

### Installation

1. **Copy the environment file:**
   ```bash
   cp .env.example .env
   ```

2. **Install PHP dependencies & start Sail:**
   ```bash
   ./vendor/bin/sail up -d
   ./vendor/bin/sail composer install
   ```

3. **Generate app key, run migrations, and link public storage:**
   ```bash
   ./vendor/bin/sail artisan key:generate
   ./vendor/bin/sail artisan migrate
   ./vendor/bin/sail artisan storage:link
   ```

4. **Install frontend dependencies & start the Vite dev server:**
   ```bash
   ./vendor/bin/sail npm install
   ./vendor/bin/sail npm run dev
   ```

5. **Open the app:**
   - **Application:** http://localhost (or `http://localhost:${APP_PORT}` if `APP_PORT` is customized in `.env`)
   - **API Docs (Scramble):** http://localhost/docs/api
   - **OpenAPI Spec (JSON):** http://localhost/docs/api.json

---

## Running the Local Blockchain & Smart Contract (Dev Notes)

To use the **On-Chain Notarization** feature locally, run **Anvil** alongside Laravel Sail and Vite:

### 1. Start the Local Ethereum Node (`anvil`)
Open a dedicated terminal on your host machine and run Anvil bound to `0.0.0.0` (so Sail's Docker container can reach it via `host.docker.internal:8545`):
```bash
anvil --host 0.0.0.0
```
> **Note:** Anvil runs an in-memory blockchain by default (`Chain ID: 31337`). Whenever you stop and restart `anvil`, its state resets and you must re-deploy `NoteNotary.sol` (Step 2) or run `anvil --host 0.0.0.0 --state ./anvil-state.json` to persist blockchain state across restarts.

### 2. Deploy the `NoteNotary` Smart Contract
In another terminal on your host machine, deploy `contracts/src/NoteNotary.sol` using Anvil's default Account `(0)` private key:
```bash
cd contracts
forge create src/NoteNotary.sol:NoteNotary \
  --rpc-url http://127.0.0.1:8545 \
  --private-key 0xac0974bec39a17e36ba4a6b4d238ff944bacb478cbed5efcae784d7bf4f2ff80 \
  --broadcast
```
Copy the `Deployed to:` address from the output (typically `0x5FbDB2315678afecb367f032d93F642f64180aa3`) and set it in `.env`:
```dotenv
BLOCKCHAIN_RPC_URL=http://host.docker.internal:8545
VITE_BLOCKCHAIN_RPC_URL=http://127.0.0.1:8545
VITE_CHAIN_ID=31337
VITE_NOTE_NOTARY_ADDRESS=0x5FbDB2315678afecb367f032d93F642f64180aa3
```
*(If Vite is already running, restart `npm run sail:dev` after changing `VITE_*` variables in `.env`.)*

### 3. Configure MetaMask for Local Testing
1. Open MetaMask → Click the account selector at the top → **Add account or hardware wallet** → **Import account**.
2. Paste Anvil's Account `(0)` private key (pre-funded with `10,000 ETH`):
   ```text
   0xac0974bec39a17e36ba4a6b4d238ff944bacb478cbed5efcae784d7bf4f2ff80
   ```
3. In the Vue Notes App, click **"🦊 Connect Wallet"**, make sure the imported `0xf39F...2266` account is checked, and approve switching to the **Anvil Localhost** network (`Chain ID: 31337`).

---

## Summary of Servers for Local Development

| Service | How to Start | URL / Port | Purpose |
|---|---|---|---|
| **Laravel Sail (Docker)** | `npm run sail:start` | `http://localhost` (or `APP_PORT`) | Laravel API, MySQL/SQLite, Storage |
| **Vite Dev Server** | `npm run sail:dev` | `http://localhost:5173` | Hot-module reloading for Vue 3 & Tailwind v4 |
| **Anvil Blockchain** | `anvil --host 0.0.0.0` | `http://127.0.0.1:8545` | Local Ethereum JSON-RPC node (`Chain ID 31337`) |

## Helpful Commands

| Command | Description |
|---|---|
| `npm run sail:start` | Start Sail containers in the background (`./vendor/bin/sail up -d`) |
| `npm run sail:stop` | Stop Sail containers (`./vendor/bin/sail down`) |
| `npm run sail:restart` | Restart Sail containers |
| `npm run sail:dev` | Run the Vite dev server inside Sail |
| `npm run sail:build` | Build production frontend assets inside Sail |
| `npm run sail:bash` | Open a bash shell inside the application container |
| `npm run db:migrate` | Run database migrations inside Sail |
| `npm run db:migrate:status` | View migration status inside Sail |
| `npm run db:migrate:rollback` | Roll back the last database migration batch |
| `./vendor/bin/sail artisan test` | Run backend PHPUnit tests |

## API Endpoints

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/notes` | List all notes (newest first, includes `current_hash` & `integrity_status`) |
| `POST` | `/api/notes` | Create a note (`title`, `content`, optional `attachment`) |
| `PUT` / `PATCH` | `/api/notes/{note}` | Update a note (use `POST` with `_method=PUT` for `multipart/form-data`) |
| `POST` | `/api/notes/{note}/notarize` | Verify a note's hash on-chain via RPC and save `notarized_hash`, `tx_hash`, `wallet_address`, and `notarized_at` |
| `DELETE` | `/api/notes/{note}` | Delete a note and its attached file |

## Project Structure

- [`contracts/src/NoteNotary.sol`](contracts/src/NoteNotary.sol) — Solidity smart contract for notarizing and verifying `bytes32` note hashes on-chain
- [`routes/api.php`](routes/api.php) — RESTful `/api/notes` resource routes + `POST /api/notes/{note}/notarize`
- [`routes/web.php`](routes/web.php) — Catch-all SPA route (excludes `api`, `storage`, and `docs`)
- [`config/services.php`](config/services.php) — Blockchain RPC URL and contract address configuration
- [`app/Http/Controllers/Api/NoteController.php`](app/Http/Controllers/Api/NoteController.php) — CRUD logic, attachment storage handling, and JSON-RPC on-chain verification
- [`app/Models/Note.php`](app/Models/Note.php) — `Note` Eloquent model with computed `current_hash` and `integrity_status` attributes
- [`resources/js/App.vue`](resources/js/App.vue) — Main Vue 3 SPA component with wallet connection & notarization status badges
- [`resources/js/stores/useNoteStore.js`](resources/js/stores/useNoteStore.js) — Pinia store for notes state, wallet state, and API requests
- [`resources/js/services/web3.js`](resources/js/services/web3.js) — Viem public/wallet clients, MetaMask chain switching, and `NoteNotary` contract calls

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
