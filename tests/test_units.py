"""Unit tests for helpers that do not need an HTTP request."""
import io
import logging
import ssl
import uuid

import pytest
from dotenv import set_key

from conftest import ENV_PATH

from app import _require_secret_key
from app.blueprints import mail_sender
from app.blueprints.buildings import _COLOR_RE, _ICON_RE
from app.blueprints.crypto import decrypt_ballot, encrypt_ballot, generate_rsa_keys, load_private_key
from app.blueprints.logutil import SafeFormatter, escape_log_value
from app.blueprints.votix import check_secret, generate_secret


# ── SECRET_KEY ─────────────────────────────────────────────────────────────

@pytest.mark.parametrize('value', [None, '', 'changeme', 'CHANGEME', 'a-long-random-string', 'short-key'])
def test_require_secret_key_rejects_weak_values(value):
    with pytest.raises(RuntimeError):
        _require_secret_key(value)


def test_require_secret_key_accepts_long_random_value():
    key = 'f' * 64
    assert _require_secret_key(key) == key


# ── Ballot cryptography ────────────────────────────────────────────────────

def test_ballot_encryption_round_trip():
    pubkey, encrypted_privkey = generate_rsa_keys('passphrase')
    voter_uuid = str(uuid.uuid4())

    ballot = encrypt_ballot('3', pubkey, voter_uuid)
    private_key = load_private_key(encrypted_privkey, 'passphrase')

    assert decrypt_ballot(ballot, private_key) == f'3/{voter_uuid}'


def test_ballot_encryption_is_randomized():
    pubkey, _ = generate_rsa_keys('passphrase')
    assert encrypt_ballot('1', pubkey, 'u') != encrypt_ballot('1', pubkey, 'u')


def test_private_key_requires_passphrase():
    _, encrypted_privkey = generate_rsa_keys('right')
    with pytest.raises(Exception):
        load_private_key(encrypted_privkey, 'wrong')


# ── Voter secret ───────────────────────────────────────────────────────────

def test_generate_secret_is_four_digits():
    for _ in range(200):
        secret = generate_secret()
        assert len(secret) == 4 and secret.isdigit()


@pytest.mark.parametrize('expected, provided, result', [
    ('0420', '0420', True),
    ('0420', '420', False),
    ('0420', '0421', False),
    ('0420', '', False),
    ('0420', None, False),
    (None, '', True),
])
def test_check_secret(expected, provided, result):
    assert check_secret(expected, provided) is result


# ── Log forging protection ─────────────────────────────────────────────────

def test_escape_log_value():
    assert escape_log_value('a\nb\r\nc') == 'a\\nb\\r\\nc'
    assert escape_log_value(42) == '42'


def test_safe_formatter_keeps_one_line_per_record():
    stream = io.StringIO()
    handler = logging.StreamHandler(stream)
    handler.setFormatter(SafeFormatter())
    logger = logging.getLogger('votix-test-safe-formatter')
    logger.addHandler(handler)
    logger.propagate = False
    try:
        logger.warning('Building added: x\n2026-01-01 00:00:00,000 - Deliberation successful')
    finally:
        logger.removeHandler(handler)

    output = stream.getvalue()
    assert output.count('\n') == 1
    assert '\\n2026-01-01' in output


# ── Building icon / colour validation ──────────────────────────────────────

@pytest.mark.parametrize('icon', ['building', 'house-door', 'building-fill', 'bank2'])
def test_valid_icons(icon):
    assert _ICON_RE.fullmatch(icon)


@pytest.mark.parametrize('icon', ['', 'Building', 'house door', 'x"onmouseover', 'a\nb', '-lead'])
def test_invalid_icons(icon):
    assert not _ICON_RE.fullmatch(icon)


@pytest.mark.parametrize('color', ['#2563eb', '#FFFFFF', '#000000'])
def test_valid_colors(color):
    assert _COLOR_RE.fullmatch(color)


@pytest.mark.parametrize('color', ['', 'red', '#fff', '#2563eb;background:url(x)', '#12345g', '#2563eb\n'])
def test_invalid_colors(color):
    assert not _COLOR_RE.fullmatch(color)


# ── Mail rendering ─────────────────────────────────────────────────────────

def test_html_mail_templates_escape_voter_data():
    payload = '<a href="https://evil.example">Cliquez ici</a><!--'
    html = mail_sender.env.get_template('send-link.html').render(
        first_name=payload, voting_link='https://votix.test/vote/abc', secret_code='1234',
        voting_start='-', voting_end='-', association_name='Assoc')

    assert payload not in html
    assert '&lt;a href=&#34;https://evil.example&#34;&gt;' in html
    assert '1234' in html


def test_plain_text_admin_mail_is_not_html_escaped():
    text = mail_sender.plain_env.get_template('admin-test.html').render(
        association_name="Bureau des Élèves d'ESIEE", smtp_server='smtp.example')
    assert "d'ESIEE" in text
    assert '&#39;' not in text


def test_validate_email_domain(app):
    assert mail_sender.validate_email_domain('someone@esiee.fr')
    assert not mail_sender.validate_email_domain('someone@gmail.com')
    assert not mail_sender.validate_email_domain('not-an-email')


# ── SMTP TLS settings ──────────────────────────────────────────────────────

class _FakeSMTP:
    instances = []

    def __init__(self, host, port, context=None):
        self.host, self.port, self.context = host, port, context
        self.sent = []
        _FakeSMTP.instances.append(self)

    def __enter__(self):
        return self

    def __exit__(self, *exc):
        return False

    def login(self, username, password):
        self.credentials = (username, password)

    def sendmail(self, sender, recipient, message):
        self.sent.append((sender, recipient, message))


@pytest.fixture
def fake_smtp(monkeypatch):
    _FakeSMTP.instances = []
    monkeypatch.setattr(mail_sender.smtplib, 'SMTP_SSL', _FakeSMTP)
    return _FakeSMTP


def test_send_email_verifies_hostname_and_certificate(fake_smtp):
    mail_sender.send_email('Subject', '<p>Hello</p>', 'voter@esiee.fr')

    server = fake_smtp.instances[0]
    assert server.host == 'smtp.invalid'
    assert server.port == 465
    assert server.context.check_hostname is True
    assert server.context.verify_mode == ssl.CERT_REQUIRED
    assert server.sent[0][1] == 'voter@esiee.fr'


def test_send_email_can_explicitly_disable_verification(fake_smtp):
    set_key(str(ENV_PATH), 'SMTP_VERIFY_SSL', 'False')

    mail_sender.send_email('Subject', 'body', 'voter@esiee.fr', mime='plain')

    context = fake_smtp.instances[0].context
    assert context.check_hostname is False
    assert context.verify_mode == ssl.CERT_NONE
