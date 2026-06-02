# Votix

A secure, self-hosted electronic voting application built with Flask. Votix lets you run end-to-end encrypted elections: ballots are encrypted with an RSA public key at the moment of the vote, and the private key never touches the server after the ARM phase — ensuring that results can only be revealed by the administrator who holds the key offline.

## Features

- **RSA-encrypted ballots** — votes are encrypted with a 2 048-bit public key; the private key is downloaded and deleted from the server immediately after generation
- **Unique voting links** — each voter receives a personal UUID-based URL and a 4-digit secret code
- **Time-gated voting window** — configurable start/end timestamps enforced on every vote request
- **Email workflows** — send invitations, voting links, and reminders in bulk via SMTP (background threads, rate-limited)
- **Email domain whitelist** — restrict registration to specific organisational domains
- **Candidate management** — add, edit, and delete candidates with optional logo upload (auto-converted to WebP)
- **CSV voter import** — bulk-import voters from a spreadsheet, including building assignment
- **Building management** — define buildings with a custom Bootstrap icon and colour; homepage participation stats are split per building; voters not assigned to any building appear in a separate card
- **Google Workspace integration** — connect a Google Workspace account to enable email auto-complete when registering voters individually
- **Palmarès** — public page showing the history of past elections, loaded from a remote YAML file (GitHub or other); can be enabled/disabled and configured from the admin panel
- **Role-based access** — `admin` and `technician` roles with separate permission levels
- **Deliberation UI** — upload the encrypted private key at deliberation time to decrypt and tally votes in the browser
- **CLI tools** — batch email sending, voter import, user creation, and reset from the command line
- **Docker-ready** — single `compose.yml` for production deployment with Gunicorn

## Security model

```text
ARM phase        Generate RSA key pair.
                 Public key stays on the server.
                 Encrypted private key is downloaded → stored offline by the admin.
                 Private key file is deleted from the server.

Vote phase       Each ballot is encrypted with the public key before being stored.
                 The server never sees plaintext votes.

Deliberation     Admin uploads the encrypted private key + passphrase.
                 Ballots are decrypted in memory, tallied, then the key file is deleted.
                 The server never persists the private key.
```

## Requirements

- Docker and Docker Compose, **or**
- Python 3.10+ with `pip`

## Quick start with Docker Compose

```bash
# 1. Clone the repository
git clone https://github.com/Isnubi/votix.git
cd votix

# 2. Configure the environment
cp app/.env.example app/.env
$EDITOR app/.env          # fill in at minimum SECRET_KEY and SMTP_* values

# 3. Start the stack
docker compose up -d

# 4. Create the first admin user
docker compose exec votix python cli.py create-user --role admin
```

The application is now reachable at `http://localhost:5000`.

## Running locally (without Docker)

```bash
pip install -r app/requirements.txt
cp app/.env.example app/.env
# Edit app/.env, then:
python cli.py create_user --role admin
gunicorn -w 4 -b 0.0.0.0:5000 start:app
```

## Configuration

All runtime settings live in `app/.env`. Copy `app/.env.example` as a starting point.

| Variable | Description | Example |
| --- | --- | --- |
| `ASSOCIATION_NAME` | Name of the association | `Bureau des Elèves d'ESIEE Paris` |
| `SECRET_KEY` | Flask session secret — change this | `a-long-random-string` |
| `VOTING_URL` | Public base URL used in voting-link emails | `https://votix.example.com/vote` |
| `VOTING_START` | Unix timestamp for when voting opens | `1779400801` |
| `VOTING_END` | Unix timestamp for when voting closes | `1779458400` |
| `VALID_EMAIL_DOMAINS` | Comma-separated list of allowed voter email domains | `esiee.fr,edu.esiee.fr` |
| `PROMOTION_LIST` | Comma-separated list of promotion/class labels | `E1,E2,E3,E4,E5` |
| `SMTP_SERVER` | SMTP hostname | `ssl0.ovh.net` |
| `SMTP_PORT` | SMTP port (SSL) | `465` |
| `SMTP_USERNAME` | SMTP login | `no-reply@example.com` |
| `SMTP_PASSWORD` | SMTP password | `changeme` |
| `SMTP_FROM` | Sender address | `no-reply@example.com` |
| `SMTP_REPLY_TO` | Reply-to address | `admin@example.com` |
| `SMTP_VERIFY_SSL` | Verify SSL certificate (`True`/`False`) | `True` |
| `GOOGLE_CLIENT_ID` | OAuth2 client ID for Google Workspace integration | *(from Google Cloud Console)* |
| `GOOGLE_CLIENT_SECRET` | OAuth2 client secret for Google Workspace integration | *(from Google Cloud Console)* |
| `HALLOFFAME_ENABLED` | Show the Palmarès page (`True`/`False`) — disable for associations without vote history | `True` |
| `HALLOFFAME_URL` | Raw URL of the `halloffame.yml` file | *(GitHub raw URL)* |
| `HALLOFFAME_CACHE_TTL` | How long (seconds) to cache the remote YAML before re-fetching | `3600` |

Most of these can also be updated at runtime from the `/configure` admin page without restarting the application.

## Voting workflow

### 1. Setup

1. Log in as **admin** and go to `/configure` to set the voting period, SMTP settings, and the public voting URL.
2. *(Optional)* Go to `/buildings` to create the buildings your voters belong to (e.g. campus sites or departments). Each building gets a Bootstrap icon and a colour; participation statistics on the homepage are split per building.
3. Register candidates at `/register-candidate` (technician or admin).
4. Import voters via CSV at `/import-voters`, or register them individually at `/register-voter`.

CSV format (with header row):

```csv
last_name,first_name,email,promotion,building
Dupont,Alice,alice.dupont@esiee.fr,E3,Perrault
Martin,Bob,bob.martin@esiee.fr,E2,
```

The `building` column is optional. If left empty, the voter appears in the **Non assigné** card on the homepage. If no buildings are configured at all, a single aggregate card is shown instead.

### 2. ARM the election

Go to `/arm` (admin only) and click **Arm**. The application will:

- Generate a 2 048-bit RSA key pair.
- Display a one-time passphrase — **save it**.
- Let you download the encrypted private key — **store it offline**.
- Delete the private key from the server.

> Once armed, the public key is stored on the server and voting can begin.

### 3. Send emails

From `/configure → Envoi des emails` (or the CLI) you can send:

- **Invitations** — notify voters that an election is coming.
- **Voting links** — send each voter their personal link + secret code.
- **Reminders** — nudge voters who have not voted yet.

### 4. Voting

Voters receive a link like `https://votix.example.com/vote/<uuid>`. They select a candidate and enter their 4-digit secret code. The ballot is encrypted and stored. A voter can only vote once.

### 5. Deliberation

After voting closes, go to `/deliberate` (admin only):

1. Upload the encrypted private key file.
2. Enter the passphrase saved during the ARM phase.
3. The results are displayed on screen. The uploaded key file is deleted immediately.

## Palmarès (vote history)

The `/palmares` page displays the history of past elections loaded from a remote YAML file. It is public (no login required) and is enabled by default.

**YAML format** (hosted externally, e.g. on GitHub):

```yaml
archives:
  2026:
    year: 2026
    winner: Azur
    voters: 607
    candidates:
      - name: Azur
        votes: 358
        color: '#79aee4'
        electable: true
        votable: true
      - name: Blanc
        votes: 22
        color: '#eeeeee'
        electable: false
        votable: true
```

Configure the URL, cache duration, and enable/disable toggle from `/configure → Palmarès`. The remote file is fetched at most once per cache period and the cache is invalidated automatically when the URL is changed. Set `HALLOFFAME_ENABLED=False` (or uncheck the option in the admin panel) to hide the page entirely for associations that have no vote history.

## Google Workspace integration

Votix can connect to a Google Workspace account to provide email auto-complete when registering voters individually at `/register-voter`.

1. Create an OAuth2 client (type **Web application**) in the Google Cloud Console and enable the **People API** and **Admin SDK Directory API**.
2. Add the application's callback URL (`https://<host>/auth/google/callback`) to the authorised redirect URIs.
3. Set `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET` in `app/.env`.
4. Go to `/configure → Google Workspace` and click **Connecter un compte Google Workspace**.

Once connected, a search box appears on the voter registration form. The connection token is stored in `app/var/google_token.json` and can be revoked from the same configure tab.

## CLI reference

Run commands against the running application:

```bash
# Inside Docker
docker compose exec votix python cli.py <command>

# Locally
python cli.py <command>
```

| Command | Description |
| --- | --- |
| `create_user --role admin\|technician` | Interactively create a system user |
| `import_voters --file voters.csv [--send-link]` | Import voters from CSV, optionally send voting links |
| `validate_csv --file voters.csv` | Validate a CSV file before import |
| `send_invitations` | Send invitation emails to voters who haven't received one |
| `send_links` | Send voting-link emails to voters who haven't received one |
| `send_reminders` | Send reminder emails to voters who haven't voted yet |
| `send_admin_test` | Send a test email to the admin to verify SMTP configuration |
| `reset` | Wipe the database and RSA keys (confirmation required) |

## Roles

| Role | Capabilities |
| --- | --- |
| `admin` | Everything: configure, arm, deliberate, manage users, reset, send bulk emails |
| `technician` | Register voters and candidates, manage buildings, view dashboard and voter list, send individual voting links |

## Data persistence (Docker)

The `compose.yml` mounts two host directories:

```text
./data   →  /app/app/var       (SQLite database, RSA public key, Google token)
./logs   →  /app/app/logs      (rotating log files)
./data/uploads → /app/app/static/uploads  (candidate logos)
```

Back up `./data/db.sqlite` regularly. The private key is never stored here — it lives only on the admin's machine.

## Logs

Separate log files are written under `logs/`:

| File | Contents |
| --- | --- |
| `app.log` | General application events |
| `admin.log` | Admin and configuration actions (ARM, deliberation, user management) |
| `votix.log` | Voter and candidate operations |
| `email.log` | Email delivery events |
| `database.log` | Database operations |
