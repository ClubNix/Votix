"""Shared fixtures for the Votix test-suite.

The application uses paths relative to the working directory ('./app/.env',
'app/var/db.sqlite', './app/logs/...'). To never touch a developer's real
database, keys or configuration, the whole ``app`` package is copied into a
temporary directory, a test ``.env`` is written there, and the tests run from
that directory.
"""
import os
import re
import shutil
import sys
import tempfile
import time
import uuid
from pathlib import Path

import pytest

REPO_ROOT = Path(__file__).resolve().parent.parent
WORK_DIR = Path(tempfile.mkdtemp(prefix='votix-tests-'))
APP_DIR = WORK_DIR / 'app'
ENV_PATH = APP_DIR / '.env'

TEST_SECRET_KEY = 'test-secret-key-' + 'x' * 48
VALID_DOMAIN = 'esiee.fr'


def base_env() -> dict:
    now = int(time.time())
    return {
        'ASSOCIATION_NAME': 'Test Association',
        'SECRET_KEY': TEST_SECRET_KEY,
        'SESSION_COOKIE_SECURE': 'False',
        'SMTP_SERVER': 'smtp.invalid',
        'SMTP_PORT': '465',
        'SMTP_USERNAME': 'no-reply@votix.test',
        'SMTP_PASSWORD': 'smtp-password',
        'SMTP_FROM': 'no-reply@votix.test',
        'SMTP_REPLY_TO': 'no-reply@votix.test',
        'SMTP_VERIFY_SSL': 'True',
        'VOTING_URL': 'https://votix.test/vote',
        'VALID_EMAIL_DOMAINS': VALID_DOMAIN,
        'PROMOTION_LIST': 'E1,E2,E3,OTHER',
        'VOTING_START': str(now - 3600),
        'VOTING_END': str(now + 3600),
        'GOOGLE_CLIENT_ID': '',
        'GOOGLE_CLIENT_SECRET': '',
        'OAUTHLIB_INSECURE_TRANSPORT': '0',
        'HALLOFFAME_ENABLED': 'False',
        'HALLOFFAME_URL': 'https://example.invalid/halloffame.yml',
        'HALLOFFAME_CACHE_TTL': '3600',
    }


def write_env(values: dict) -> None:
    ENV_PATH.write_text(''.join(f"{k}='{v}'\n" for k, v in values.items()), encoding='utf-8')


def _prepare_workdir() -> None:
    shutil.copytree(
        REPO_ROOT / 'app', APP_DIR,
        ignore=shutil.ignore_patterns('__pycache__', '.env', '*.sqlite', '*.pem', '*.log',
                                      'google_token.json', 'google_oauth_state.json', 'uploads'),
    )
    for sub in ('var', 'logs', 'static/uploads'):
        (APP_DIR / sub).mkdir(parents=True, exist_ok=True)
    write_env(base_env())
    os.chdir(WORK_DIR)
    sys.path.insert(0, str(WORK_DIR))


_prepare_workdir()

import email_validator  # noqa: E402

# Never perform DNS lookups while validating emails in tests.
email_validator.CHECK_DELIVERABILITY = False

from werkzeug.security import generate_password_hash  # noqa: E402

from app import create_app, db as sa_db  # noqa: E402
from app.models import Building, Candidate, User, Voter  # noqa: E402

CSRF_RE = re.compile(r'name="csrf_token" value="([^"]+)"')


@pytest.fixture(scope='session')
def app():
    application = create_app()
    application.config.update(TESTING=True)
    yield application
    os.chdir(REPO_ROOT)
    shutil.rmtree(WORK_DIR, ignore_errors=True)


@pytest.fixture(autouse=True)
def clean_state(app):
    """Fresh database rows, configuration and key files for every test."""
    write_env(base_env())
    for name in ('pubkey.pem', 'privkey.pem'):
        (APP_DIR / 'var' / name).unlink(missing_ok=True)
    with app.app_context():
        for model in (Voter, Candidate, Building, User):
            model.query.delete()
        sa_db.session.commit()
    yield
    with app.app_context():
        sa_db.session.remove()


@pytest.fixture
def client(app):
    return app.test_client()


# ── Helpers ────────────────────────────────────────────────────────────────

def get_csrf_token(client) -> str:
    """Return the CSRF token bound to the client's session."""
    html = client.get('/login').get_data(as_text=True)
    match = CSRF_RE.search(html)
    assert match, 'login page should contain a CSRF token'
    return match.group(1)


def post(client, url, data=None, **kwargs):
    """POST with a valid CSRF token added to the form."""
    data = dict(data or {})
    data['csrf_token'] = get_csrf_token(client)
    return client.post(url, data=data, **kwargs)


def create_user(app, email, password='password', role='admin', username=None) -> int:
    with app.app_context():
        user = User(email=email, username=username or email.split('@')[0],
                    password=generate_password_hash(password, method='scrypt'), role=role)
        sa_db.session.add(user)
        sa_db.session.commit()
        return user.id


def login(client, email, password='password'):
    return post(client, '/login', {'email': email, 'password': password})


def create_voter(app, email=f'voter@{VALID_DOMAIN}', secret='1234', **fields) -> dict:
    with app.app_context():
        voter = Voter(last_name=fields.get('last_name', 'doe'), first_name=fields.get('first_name', 'jane'),
                      email=email, promotion=fields.get('promotion', 'E1'), building=fields.get('building', ''),
                      link_string=str(uuid.uuid4()), secret=secret, voted=fields.get('voted', False),
                      invitation_sent=False, link_sent=False, failed_attempts=0)
        sa_db.session.add(voter)
        sa_db.session.commit()
        return {'id': voter.id, 'link_string': voter.link_string, 'secret': secret, 'email': email}


def create_candidate(app, name='Liste A', eligible=True) -> int:
    with app.app_context():
        candidate = Candidate(name=name, eligible=eligible, logo='')
        sa_db.session.add(candidate)
        sa_db.session.commit()
        return candidate.id


def get_voter(app, voter_id):
    with app.app_context():
        voter = sa_db.session.get(Voter, voter_id)
        sa_db.session.expunge(voter)
        return voter


@pytest.fixture
def admin_id(app):
    return create_user(app, f'admin@{VALID_DOMAIN}', role='admin', username='admin')


@pytest.fixture
def technician_id(app):
    return create_user(app, f'tech@{VALID_DOMAIN}', role='technician', username='tech')


@pytest.fixture
def admin_client(client, admin_id):
    response = login(client, f'admin@{VALID_DOMAIN}')
    assert response.status_code == 302
    return client


@pytest.fixture
def technician_client(client, technician_id):
    response = login(client, f'tech@{VALID_DOMAIN}')
    assert response.status_code == 302
    return client
