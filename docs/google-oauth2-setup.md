# Google OAuth2 Setup — Google Workspace Directory Search

This guide explains how to configure Google OAuth2 so that Votix can search your Google Workspace directory when registering voters.

## Prerequisites

- A Google account (personal `@gmail.com` works — no need for a Workspace admin account)
- Access to [console.cloud.google.com](https://console.cloud.google.com)

---

## Step 1 — Create a Google Cloud Project

1. Go to [console.cloud.google.com](https://console.cloud.google.com)
2. Click the project selector at the top → **New Project**
3. Name it (e.g. `votix`) → **Create**
4. Make sure the new project is selected in the top bar

---

## Step 2 — Enable the People API

1. In the left menu: **APIs & Services** → **Library**
2. Search for **People API**
3. Click it → **Enable**

---

## Step 3 — Configure the OAuth Consent Screen

1. **APIs & Services** → **OAuth consent screen**
2. Choose **User type**:
   - **Internal** — if the project is inside your Google Workspace org (requires Workspace admin to create the project)
   - **External** — if the project is on a personal Google account. Works fine for this use case; users will see a "Google hasn't verified this app" warning but can click through
3. Click **Create**
4. Fill in:
   - **App name**: `Votix`
   - **User support email**: your email
   - **Developer contact email**: your email
5. Click **Save and Continue**
6. On the **Scopes** screen, click **Add or Remove Scopes** and add:
   - `https://www.googleapis.com/auth/directory.readonly`
   - `https://www.googleapis.com/auth/userinfo.email`
   - `openid`
7. **Save and Continue**
8. If **External**: on the **Test users** screen, add every `@edu.yourdomain.fr` email address that will connect to Votix (i.e. the admin account). Users not in this list will be blocked while the app is unverified.
9. **Save and Continue** → **Back to Dashboard**

---

## Step 4 — Create OAuth Credentials

1. **APIs & Services** → **Credentials** → **+ Create Credentials** → **OAuth client ID**
2. Application type: **Web application**
3. Name: `Votix Web`
4. Under **Authorized redirect URIs**, add your callback URL(s):
   - Local: `http://localhost:5000/auth/google/callback`
   - Production: `https://your-domain.fr/auth/google/callback`
5. Click **Create**
6. Copy the **Client ID** and **Client Secret**

---

## Step 5 — Configure Votix

Add the credentials to `app/.env`:

```env
GOOGLE_CLIENT_ID=your_client_id_here
GOOGLE_CLIENT_SECRET=your_client_secret_here
```

For local development only (disables the HTTPS requirement):

```env
OAUTHLIB_INSECURE_TRANSPORT=1
```

> **Remove `OAUTHLIB_INSECURE_TRANSPORT` in production.** The app enforces HTTPS automatically when this variable is absent.

---

## Step 6 — Connect the Google Account

1. Start the app and log in as **admin**
2. Go to **Configuration** → **Google Workspace** tab
3. Click **Connecter un compte Google Workspace**
4. Sign in with a Google account that belongs to your school domain (e.g. `yourname@edu.yourdomain.fr`)
5. Accept the requested permissions

The token is saved to `app/var/google_token.json` and refreshes automatically — you only need to do this once.

---

## How It Works After Setup

- On the **Inscrire un électeur** page, a search field appears above the form
- Type a name (2+ characters) → results from your Google Workspace directory appear
- Selecting a result auto-fills the last name (CAPS), first name, and email fields
- The search is available to all technician and admin accounts; only admins can connect or disconnect the Google account

---

## Step 7 — Publish the App (remove the "unverified" warning)

While the app is in **Testing** mode, only explicitly added test users can connect, and everyone sees a scary "Google hasn't verified this app" warning. To remove both restrictions you need to publish the app.

> **Note:** `directory.readonly` is classified as a **sensitive scope** by Google. Publishing an app that requests it requires a manual review by Google. For a private student association tool, the easiest path is to stay in Testing mode and simply add all admin emails as test users — Google allows up to 100.

### Option A — Stay in Testing mode (recommended for internal use)

1. **APIs & Services** → **OAuth consent screen** → **Test users**
2. Add every admin/technician email that will ever connect Votix to Google
3. No review needed — they can connect immediately

### Option B — Submit for Google verification

Only do this if you genuinely need more than 100 test users or want to remove the warning for end users (not needed here since only admins connect).

1. **APIs & Services** → **OAuth consent screen** → click **Publish App**
2. Google will warn that the `directory.readonly` scope requires verification — click **Confirm**
3. The app moves to **In production** but shows an "unverified" interstitial until review is complete
4. Click **Prepare for verification** and fill in:
   - **Privacy policy URL** — a public URL hosting your privacy policy (required)
   - **Authorized domains** — your production domain (e.g. `votix.fr`)
   - Justification for each sensitive scope (explain you're searching your own school directory)
5. Submit — Google's review typically takes several days to weeks
6. Once approved the warning disappears for all users in your domain

---

## Step 8 — Production deployment

### 1. Register the production redirect URI

In **APIs & Services** → **Credentials** → your OAuth client → **Edit**, add:

```text
https://your-domain.fr/auth/google/callback
```

### 2. Set environment variables on the server

In `app/.env` on the production server:

```env
GOOGLE_CLIENT_ID=your_client_id_here
GOOGLE_CLIENT_SECRET=your_client_secret_here
# Do NOT set OAUTHLIB_INSECURE_TRANSPORT in production
```

`OAUTHLIB_INSECURE_TRANSPORT` must be absent (or set to `0`) in production — the app enforces HTTPS automatically.

### 3. Rebuild and restart

```bash
docker compose build
docker compose up -d
```

### 4. Reconnect the Google account

The token file (`app/var/google_token.json`) is environment-specific. After deploying:

1. Log in as admin on the production URL
2. Go to **Configuration** → **Google Workspace** tab
3. Click **Connecter un compte Google Workspace** and sign in with the school account

The token persists across restarts as long as the `app/var/` volume is mounted.

---

## Troubleshooting

| Error | Cause | Fix |
|-------|-------|-----|
| `insecure_transport` | App is running over HTTP | Set `OAUTHLIB_INSECURE_TRANSPORT=1` in `.env` (local only) |
| `Missing code verifier` | PKCE mismatch between auth and token steps | Already handled in the implementation — ensure you're on the latest version |
| `Access blocked: app not verified` | User not in the test users list | Add the email in Cloud Console → OAuth consent screen → Test users |
| `redirect_uri_mismatch` | Callback URL not registered | Add the exact URL to Authorized redirect URIs in your OAuth credential |
| Empty search results | Account not in the Workspace directory | Confirm the connected account belongs to the school domain |
| Token expired / `invalid_grant` | Refresh token revoked | Disconnect and reconnect from the Google Workspace tab in Configuration |
