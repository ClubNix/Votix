"""Voting flow: casting ballots, secret code checks and lockout."""
import time

import pytest
from dotenv import set_key

from conftest import APP_DIR, ENV_PATH, create_candidate, create_voter, get_csrf_token, get_voter, post

from app.blueprints import votix as votix_module
from app.blueprints.crypto import decrypt_ballot, generate_rsa_keys, load_private_key


@pytest.fixture
def keys():
    pubkey, privkey = generate_rsa_keys('passphrase')
    (APP_DIR / 'var' / 'pubkey.pem').write_bytes(pubkey)
    return privkey


@pytest.fixture
def election(app, keys):
    return {
        'voter': create_voter(app, secret='4821'),
        'candidate_id': create_candidate(app, 'Liste A'),
        'privkey': keys,
    }


def _vote(client, link, candidate_id, secret):
    return post(client, f'/vote/{link}', {'candidate': str(candidate_id), 'secret': secret})


def test_vote_page_renders(client, election):
    response = client.get(f"/vote/{election['voter']['link_string']}")
    assert response.status_code == 200
    assert 'Liste A' in response.get_data(as_text=True)


def test_unknown_link(client, election):
    response = client.get('/vote/does-not-exist')
    assert "Ce lien de vote n&#39;existe pas." in response.get_data(as_text=True)


def test_successful_vote_is_encrypted_and_decryptable(client, app, election):
    voter = election['voter']
    response = _vote(client, voter['link_string'], election['candidate_id'], '4821')

    assert 'Vote enregistré avec succès.' in response.get_data(as_text=True)
    stored = get_voter(app, voter['id'])
    assert stored.voted

    private_key = load_private_key(election['privkey'], 'passphrase')
    ballot = stored.ballot if isinstance(stored.ballot, bytes) else bytes(stored.ballot)
    assert decrypt_ballot(ballot, private_key) == f"{election['candidate_id']}/{voter['link_string']}"


def test_cannot_vote_twice(client, app, election):
    voter = election['voter']
    _vote(client, voter['link_string'], election['candidate_id'], '4821')
    response = _vote(client, voter['link_string'], election['candidate_id'], '4821')
    assert 'Cet électeur a déjà voté.' in response.get_data(as_text=True)


def test_vote_without_csrf_token_is_rejected(client, app, election):
    voter = election['voter']
    response = client.post(f"/vote/{voter['link_string']}",
                           data={'candidate': str(election['candidate_id']), 'secret': '4821'})
    assert response.status_code == 400
    assert not get_voter(app, voter['id']).voted


def test_vote_for_ineligible_candidate_is_refused(client, app, election):
    ineligible = create_candidate(app, 'Liste B', eligible=False)
    response = _vote(client, election['voter']['link_string'], ineligible, '4821')
    assert 'Candidat invalide ou non éligible.' in response.get_data(as_text=True)
    assert not get_voter(app, election['voter']['id']).voted


def test_wrong_secret_is_counted(client, app, election):
    voter = election['voter']
    response = _vote(client, voter['link_string'], election['candidate_id'], '0000')

    assert 'Code secret invalide.' in response.get_data(as_text=True)
    stored = get_voter(app, voter['id'])
    assert not stored.voted
    assert stored.failed_attempts == 1


def test_link_is_locked_after_too_many_wrong_secrets(client, app, election):
    voter = election['voter']
    get_csrf_token(client)
    for attempt in range(votix_module.MAX_SECRET_ATTEMPTS):
        _vote(client, voter['link_string'], election['candidate_id'], f'{attempt:04d}' if attempt != 4821 else '9999')

    response = _vote(client, voter['link_string'], election['candidate_id'], '4821')

    assert 'bloqué' in response.get_data(as_text=True)
    stored = get_voter(app, voter['id'])
    assert not stored.voted
    assert stored.failed_attempts == votix_module.MAX_SECRET_ATTEMPTS


def test_resending_link_unlocks_voter(technician_client, app, election, monkeypatch):
    voter = election['voter']
    from app import db as sa_db
    from app.models import Voter
    with app.app_context():
        sa_db.session.get(Voter, voter['id']).failed_attempts = votix_module.MAX_SECRET_ATTEMPTS
        sa_db.session.commit()

    sent = []

    def fake_send_link_email(v):
        sent.append(v.email)
        v.link_sent = True
        sa_db.session.commit()

    monkeypatch.setattr(votix_module, 'send_link_email', fake_send_link_email)
    response = post(technician_client, f"/voters/{voter['id']}/send-link")

    assert response.status_code == 302
    assert sent == [voter['email']]
    assert get_voter(app, voter['id']).failed_attempts == 0


def test_voting_closed(client, election):
    set_key(str(ENV_PATH), 'VOTING_END', str(int(time.time()) - 60))
    response = client.get(f"/vote/{election['voter']['link_string']}")
    assert 'Le vote est terminé.' in response.get_data(as_text=True)


def test_voting_not_started(client, election):
    set_key(str(ENV_PATH), 'VOTING_START', str(int(time.time()) + 3600))
    response = client.get(f"/vote/{election['voter']['link_string']}")
    assert "Le vote n&#39;a pas encore commencé." in response.get_data(as_text=True)


def test_index_page_shows_participation(client, app, election):
    response = client.get('/')
    assert response.status_code == 200
