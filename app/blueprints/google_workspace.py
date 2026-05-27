import os
import json
import logging
import hashlib
import base64
import secrets

from flask import Blueprint, redirect, request, url_for, jsonify, flash
from flask_login import login_required
from google_auth_oauthlib.flow import Flow
from google.oauth2.credentials import Credentials
from google.auth.transport.requests import Request
from googleapiclient.discovery import build

from .auth import admin_required, technician_required

google_ws = Blueprint('google_ws', __name__)

# Allow HTTP for local dev. Set OAUTHLIB_INSECURE_TRANSPORT=1 in .env to enable.
if os.getenv('OAUTHLIB_INSECURE_TRANSPORT') == '1':
    os.environ['OAUTHLIB_INSECURE_TRANSPORT'] = '1'
logger = logging.getLogger(__name__)

SCOPES = [
    'openid',
    'https://www.googleapis.com/auth/userinfo.email',
    'https://www.googleapis.com/auth/directory.readonly',
]

TOKEN_PATH = os.path.join(os.path.dirname(__file__), '..', 'var', 'google_token.json')
STATE_PATH = os.path.join(os.path.dirname(__file__), '..', 'var', 'google_oauth_state.json')


def _client_config():
    return {
        'web': {
            'client_id': os.getenv('GOOGLE_CLIENT_ID'),
            'client_secret': os.getenv('GOOGLE_CLIENT_SECRET'),
            'auth_uri': 'https://accounts.google.com/o/oauth2/auth',
            'token_uri': 'https://oauth2.googleapis.com/token',
            'redirect_uris': [],
        }
    }


def get_credentials():
    """Load stored credentials, refresh if expired. Returns None if not connected."""
    if not os.path.exists(TOKEN_PATH):
        return None
    with open(TOKEN_PATH) as f:
        data = json.load(f)
    creds = Credentials(
        token=data.get('token'),
        refresh_token=data.get('refresh_token'),
        token_uri=data.get('token_uri', 'https://oauth2.googleapis.com/token'),
        client_id=data.get('client_id'),
        client_secret=data.get('client_secret'),
        scopes=data.get('scopes'),
    )
    if creds.expired and creds.refresh_token:
        try:
            creds.refresh(Request())
            _save_credentials(creds)
        except Exception as e:
            logger.error(f"Failed to refresh Google token: {e}")
            return None
    return creds


def _save_credentials(creds):
    with open(TOKEN_PATH, 'w') as f:
        json.dump({
            'token': creds.token,
            'refresh_token': creds.refresh_token,
            'token_uri': creds.token_uri,
            'client_id': creds.client_id,
            'client_secret': creds.client_secret,
            'scopes': list(creds.scopes) if creds.scopes else SCOPES,
        }, f)


def is_connected():
    return os.path.exists(TOKEN_PATH)


def _save_oauth_state(state, code_verifier):
    with open(STATE_PATH, 'w') as f:
        json.dump({'state': state, 'code_verifier': code_verifier}, f)


def _pop_oauth_state():
    """Read and immediately delete the stored OAuth state (one-time use)."""
    if not os.path.exists(STATE_PATH):
        return None, None
    with open(STATE_PATH) as f:
        data = json.load(f)
    os.remove(STATE_PATH)
    return data.get('state'), data.get('code_verifier')


def _pkce_pair():
    """Generate a PKCE code_verifier and its S256 code_challenge."""
    verifier = base64.urlsafe_b64encode(secrets.token_bytes(32)).rstrip(b'=').decode()
    challenge = base64.urlsafe_b64encode(
        hashlib.sha256(verifier.encode()).digest()
    ).rstrip(b'=').decode()
    return verifier, challenge


@google_ws.route('/auth/google')
@login_required
@admin_required
def google_auth():
    flow = Flow.from_client_config(_client_config(), scopes=SCOPES)
    flow.redirect_uri = url_for('google_ws.google_callback', _external=True)
    code_verifier, code_challenge = _pkce_pair()
    authorization_url, state = flow.authorization_url(
        access_type='offline',
        prompt='consent',
        code_challenge=code_challenge,
        code_challenge_method='S256',
    )
    _save_oauth_state(state, code_verifier)
    return redirect(authorization_url)


@google_ws.route('/auth/google/callback')
def google_callback():
    """No @login_required here — the session cookie is unreliable across the
    cross-site OAuth redirect when running behind a proxy. Security is ensured
    by the state file (written only by the admin-protected /auth/google route)."""
    saved_state, code_verifier = _pop_oauth_state()
    if saved_state is None or saved_state != request.args.get('state'):
        flash('Paramètre de sécurité invalide. Recommencez la connexion.', 'danger')
        return redirect(url_for('configure.configure') + '?tab=4')

    flow = Flow.from_client_config(_client_config(), scopes=SCOPES, state=saved_state)
    flow.redirect_uri = url_for('google_ws.google_callback', _external=True)
    try:
        callback_url = request.url
        if request.headers.get('X-Forwarded-Proto') == 'https':
            callback_url = callback_url.replace('http://', 'https://', 1)
        flow.fetch_token(authorization_response=callback_url, code_verifier=code_verifier)
        _save_credentials(flow.credentials)
        flash('Google Workspace connecté avec succès.', 'success')
    except Exception as e:
        logger.error(f"Google OAuth callback error: {e}")
        flash(f'Erreur lors de la connexion Google : {e}', 'danger')
    return redirect(url_for('configure.configure') + '?tab=4')


@google_ws.route('/auth/google/disconnect', methods=['POST'])
@login_required
@admin_required
def google_disconnect():
    if os.path.exists(TOKEN_PATH):
        os.remove(TOKEN_PATH)
    flash('Google Workspace déconnecté.', 'success')
    return redirect(url_for('configure.configure') + '?tab=4')


@google_ws.route('/api/search-workspace')
@login_required
@technician_required
def search_workspace():
    query = request.args.get('q', '').strip()
    if len(query) < 2:
        return jsonify([])

    creds = get_credentials()
    if creds is None:
        return jsonify({'error': 'not_connected'}), 503

    try:
        service = build('people', 'v1', credentials=creds)
        results = service.people().searchDirectoryPeople(
            query=query,
            readMask='names,emailAddresses',
            sources=['DIRECTORY_SOURCE_TYPE_DOMAIN_PROFILE']
        ).execute()

        out = []
        for p in results.get('people', []):
            names = p.get('names', [])
            emails = p.get('emailAddresses', [])
            if not names or not emails:
                continue
            n = names[0]
            out.append({
                'first_name': n.get('givenName', ''),
                'last_name': n.get('familyName', ''),
                'display_name': n.get('displayName', ''),
                'email': emails[0].get('value', ''),
            })
        return jsonify(out)
    except Exception as e:
        logger.error(f"Google Workspace search error: {e}")
        return jsonify({'error': str(e)}), 500
