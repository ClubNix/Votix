"""Admin features: users, reset, key generation and configuration."""
from dotenv import dotenv_values

from conftest import APP_DIR, ENV_PATH, VALID_DOMAIN, create_candidate, create_user, create_voter, post

from app.models import Candidate, User, Voter

NEW_USER = {'username': 'newtech', 'email': f'newtech@{VALID_DOMAIN}', 'password': 'pw', 'role': 'technician'}


def _user(app, email):
    with app.app_context():
        return User.query.filter_by(email=email).first()


# ── Users ──────────────────────────────────────────────────────────────────

def test_admin_creates_user(admin_client, app):
    response = post(admin_client, '/users', NEW_USER)
    assert response.status_code == 302
    user = _user(app, NEW_USER['email'])
    assert user is not None and user.role == 'technician'
    assert user.password != 'pw'


def test_forged_user_creation_without_csrf_token_is_rejected(admin_client, app):
    response = admin_client.post('/users', data={**NEW_USER, 'role': 'admin'})
    assert response.status_code == 400
    assert _user(app, NEW_USER['email']) is None


def test_create_user_rejects_invalid_role(admin_client, app):
    post(admin_client, '/users', {**NEW_USER, 'role': 'superuser'})
    assert _user(app, NEW_USER['email']) is None


def test_create_user_rejects_missing_fields(admin_client, app):
    post(admin_client, '/users', {**NEW_USER, 'password': ''})
    assert _user(app, NEW_USER['email']) is None


def test_create_user_rejects_duplicate_email(admin_client, app):
    post(admin_client, '/users', NEW_USER)
    post(admin_client, '/users', {**NEW_USER, 'username': 'other'})
    with app.app_context():
        assert User.query.filter_by(email=NEW_USER['email']).count() == 1


def test_admin_deletes_user(admin_client, app):
    user_id = create_user(app, f'victim@{VALID_DOMAIN}', role='technician')
    post(admin_client, f'/users/{user_id}/delete')
    assert _user(app, f'victim@{VALID_DOMAIN}') is None


def test_admin_cannot_delete_self(admin_client, app, admin_id):
    post(admin_client, f'/users/{admin_id}/delete')
    assert _user(app, f'admin@{VALID_DOMAIN}') is not None


# ── Reset ──────────────────────────────────────────────────────────────────

def test_reset_wipes_data_but_keeps_first_admin(admin_client, app):
    create_user(app, f'second@{VALID_DOMAIN}', role='admin')
    create_voter(app)
    create_candidate(app)

    response = post(admin_client, '/reset')

    assert response.status_code == 302
    with app.app_context():
        assert Voter.query.count() == 0
        assert Candidate.query.count() == 0
        assert [u.email for u in User.query.all()] == [f'admin@{VALID_DOMAIN}']


def test_forged_reset_is_rejected(admin_client, app):
    create_voter(app)
    assert admin_client.post('/reset').status_code == 400
    with app.app_context():
        assert Voter.query.count() == 1


# ── Arm (key generation) ───────────────────────────────────────────────────

def test_arm_generates_keypair(admin_client):
    response = post(admin_client, '/arm')
    assert response.status_code == 200
    assert (APP_DIR / 'var' / 'pubkey.pem').read_bytes().startswith(b'-----BEGIN PUBLIC KEY-----')
    assert (APP_DIR / 'var' / 'privkey.pem').read_bytes().startswith(b'-----BEGIN ENCRYPTED PRIVATE KEY-----')


def test_arm_refused_once_votes_exist(admin_client, app):
    (APP_DIR / 'var' / 'pubkey.pem').write_bytes(b'existing-key')
    create_voter(app, voted=True)

    response = post(admin_client, '/arm')

    assert response.status_code == 200
    assert (APP_DIR / 'var' / 'pubkey.pem').read_bytes() == b'existing-key'


def test_forged_arm_is_rejected(admin_client):
    assert admin_client.post('/arm').status_code == 400
    assert not (APP_DIR / 'var' / 'pubkey.pem').exists()


def test_download_key_removes_private_key(admin_client):
    post(admin_client, '/arm')
    response = post(admin_client, '/download-key')
    assert response.status_code == 200
    assert response.data.startswith(b'-----BEGIN ENCRYPTED PRIVATE KEY-----')
    response.close()
    assert not (APP_DIR / 'var' / 'privkey.pem').exists()


# ── Configuration ──────────────────────────────────────────────────────────

def test_configure_updates_voting_url(admin_client):
    response = post(admin_client, '/configure', {'section': 'url', 'voting_url': 'https://new.example/vote/'})
    assert response.status_code == 302
    assert dotenv_values(ENV_PATH)['VOTING_URL'] == 'https://new.example/vote'


def test_forged_configure_is_rejected(admin_client):
    response = admin_client.post('/configure', data={'section': 'smtp', 'smtp_server': 'attacker.example'})
    assert response.status_code == 400
    assert dotenv_values(ENV_PATH)['SMTP_SERVER'] == 'smtp.invalid'


def test_configure_updates_email_domains(admin_client):
    post(admin_client, '/configure', {'section': 'domains', 'valid_domains': ' a.fr, b.fr ,,'})
    assert dotenv_values(ENV_PATH)['VALID_EMAIL_DOMAINS'] == 'a.fr,b.fr'
