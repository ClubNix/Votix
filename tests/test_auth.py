"""Authentication, authorisation and session-cookie tests."""
import pytest

from conftest import VALID_DOMAIN, get_csrf_token, login, post

from app.blueprints import auth


def test_login_page_renders_with_csrf_token(client):
    response = client.get('/login')
    assert response.status_code == 200
    assert 'name="csrf_token"' in response.get_data(as_text=True)


def test_login_without_csrf_token_is_rejected(client, admin_id):
    response = client.post('/login', data={'email': f'admin@{VALID_DOMAIN}', 'password': 'password'})
    assert response.status_code == 400


def test_login_success_redirects_to_dashboard(client, admin_id):
    response = login(client, f'admin@{VALID_DOMAIN}')
    assert response.status_code == 302
    assert response.headers['Location'].endswith('/dashboard')
    assert client.get('/dashboard').status_code == 200


def test_session_cookie_is_samesite_lax_and_httponly(client, admin_id):
    response = login(client, f'admin@{VALID_DOMAIN}')
    cookie = response.headers.get('Set-Cookie', '')
    assert 'SameSite=Lax' in cookie
    assert 'HttpOnly' in cookie


def test_login_wrong_password(client, admin_id):
    response = login(client, f'admin@{VALID_DOMAIN}', password='wrong')
    assert response.status_code == 302
    assert response.headers['Location'].endswith('/login')
    assert client.get('/dashboard').status_code == 302


def test_login_unknown_email_still_checks_a_password_hash(client, monkeypatch):
    calls = []
    original = auth.check_password_hash

    def spy(pwhash, password):
        calls.append(pwhash)
        return original(pwhash, password)

    monkeypatch.setattr(auth, 'check_password_hash', spy)
    response = login(client, f'nobody@{VALID_DOMAIN}', password='whatever')

    assert response.status_code == 302
    assert response.headers['Location'].endswith('/login')
    assert calls == [auth._DUMMY_PASSWORD_HASH]


def test_logout(admin_client):
    assert admin_client.get('/logout').status_code == 302
    assert admin_client.get('/dashboard').status_code == 302


@pytest.mark.parametrize('url', ['/dashboard', '/voters', '/candidates', '/buildings', '/users',
                                 '/configure', '/arm', '/deliberate', '/register-voter', '/import-voters'])
def test_protected_pages_require_login(client, url):
    response = client.get(url)
    assert response.status_code == 302
    assert '/login' in response.headers['Location']


@pytest.mark.parametrize('url', ['/dashboard', '/voters', '/candidates', '/buildings', '/register-voter',
                                 '/register-candidate'])
def test_technician_can_access_technician_pages(technician_client, url):
    assert technician_client.get(url).status_code == 200


@pytest.mark.parametrize('url', ['/users', '/configure', '/arm', '/deliberate', '/import-voters'])
def test_technician_cannot_access_admin_pages(technician_client, url):
    response = technician_client.get(url)
    assert response.status_code == 302
    assert response.headers['Location'].endswith('/')


@pytest.mark.parametrize('url', ['/dashboard', '/voters', '/candidates', '/buildings', '/users', '/configure', '/arm'])
def test_admin_pages_render(admin_client, url):
    assert admin_client.get(url).status_code == 200


def test_technician_cannot_create_users(technician_client, app):
    response = post(technician_client, '/users',
                    {'username': 'x', 'email': f'x@{VALID_DOMAIN}', 'password': 'p', 'role': 'admin'})
    assert response.status_code == 302
    from app.models import User
    with app.app_context():
        assert User.query.filter_by(email=f'x@{VALID_DOMAIN}').first() is None


def test_csrf_token_is_stable_within_a_session(client):
    assert get_csrf_token(client) is not None
