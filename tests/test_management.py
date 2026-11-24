"""Voter, candidate and building management, including XSS and log forging regressions."""
from conftest import APP_DIR, VALID_DOMAIN, create_candidate, create_voter, post

from app.models import Building, Candidate, Voter

XSS_PAYLOAD = "');fetch('/users');//"


# ── Voters ─────────────────────────────────────────────────────────────────

def test_technician_registers_voter(technician_client, app):
    response = post(technician_client, '/register-voter', {
        'last_name': 'Doe', 'first_name': 'Jane', 'email': f'jane@{VALID_DOMAIN}', 'promotion': 'E1',
    })
    assert response.status_code == 200
    with app.app_context():
        voter = Voter.query.filter_by(email=f'jane@{VALID_DOMAIN}').one()
        assert voter.last_name == 'doe'
        assert len(voter.secret) == 4 and voter.secret.isdigit()
        assert len(voter.link_string) == 36


def test_register_voter_rejects_other_domains(technician_client, app):
    post(technician_client, '/register-voter', {
        'last_name': 'Doe', 'first_name': 'Jane', 'email': 'jane@gmail.com', 'promotion': 'E1',
    })
    with app.app_context():
        assert Voter.query.count() == 0


def test_register_voter_rejects_duplicates(technician_client, app):
    create_voter(app, email=f'jane@{VALID_DOMAIN}')
    post(technician_client, '/register-voter', {
        'last_name': 'Doe', 'first_name': 'Jane', 'email': f'jane@{VALID_DOMAIN}', 'promotion': 'E1',
    })
    with app.app_context():
        assert Voter.query.count() == 1


def test_voter_list_does_not_leak_secrets(technician_client, app):
    voter = create_voter(app, secret='9137')
    html = technician_client.get('/voters').get_data(as_text=True)
    assert voter['email'] in html
    assert voter['link_string'] not in html
    assert '9137' not in html


def test_voter_email_is_not_injected_into_inline_javascript(admin_client, app):
    email = f"x'-alert`1`-'@{VALID_DOMAIN}"
    create_voter(app, email=email)

    html = admin_client.get('/voters').get_data(as_text=True)

    assert "onsubmit=\"return confirm(" not in html
    assert "data-confirm=\"Supprimer définitivement l'électeur x&#39;-alert`1`-&#39;@" in html


def test_admin_deletes_voter(admin_client, app):
    voter = create_voter(app)
    post(admin_client, f"/voters/{voter['id']}/delete")
    with app.app_context():
        assert Voter.query.count() == 0


def test_technician_cannot_delete_voter(technician_client, app):
    voter = create_voter(app)
    post(technician_client, f"/voters/{voter['id']}/delete")
    with app.app_context():
        assert Voter.query.count() == 1


def test_admin_imports_voters_from_csv(admin_client, app):
    import io
    csv_content = 'last_name,first_name,email,promotion,building\n' \
                  f'Doe,Jane,jane@{VALID_DOMAIN},E1,\n' \
                  f'Roe,Rick,rick@{VALID_DOMAIN},E2,B1\n'
    data = {'file': (io.BytesIO(csv_content.encode()), 'voters.csv')}
    response = post(admin_client, '/import-voters', data, content_type='multipart/form-data')
    assert response.status_code == 200
    with app.app_context():
        assert sorted(v.email for v in Voter.query.all()) == [f'jane@{VALID_DOMAIN}', f'rick@{VALID_DOMAIN}']


# ── Candidates ─────────────────────────────────────────────────────────────

def test_technician_registers_candidate(technician_client, app):
    post(technician_client, '/register-candidate', {'name': 'Liste A', 'eligible': 'on'},
         content_type='multipart/form-data')
    with app.app_context():
        candidate = Candidate.query.one()
        assert candidate.name == 'Liste A' and candidate.eligible


def test_candidate_name_is_not_injected_into_inline_javascript(technician_client, app):
    create_candidate(app, XSS_PAYLOAD)
    html = technician_client.get('/candidates').get_data(as_text=True)

    assert XSS_PAYLOAD not in html
    assert 'data-confirm="Supprimer « &#39;);fetch(&#39;/users&#39;);//' in html


def test_edit_and_delete_candidate(technician_client, app):
    candidate_id = create_candidate(app, 'Old name')
    post(technician_client, f'/edit-candidate/{candidate_id}', {'name': 'New name'},
         content_type='multipart/form-data')
    with app.app_context():
        candidate = Candidate.query.one()
        assert candidate.name == 'New name' and not candidate.eligible

    post(technician_client, f'/delete-candidate/{candidate_id}')
    with app.app_context():
        assert Candidate.query.count() == 0


# ── Buildings ──────────────────────────────────────────────────────────────

def test_technician_adds_building(technician_client, app):
    post(technician_client, '/buildings', {'name': 'Bâtiment A', 'icon': 'house-door', 'color': '#123abc'})
    with app.app_context():
        building = Building.query.one()
        assert (building.name, building.icon, building.color) == ('Bâtiment A', 'house-door', '#123abc')


def test_building_with_invalid_color_is_rejected(technician_client, app):
    post(technician_client, '/buildings', {'name': 'B', 'icon': 'building', 'color': 'red;background:url(x)'})
    with app.app_context():
        assert Building.query.count() == 0


def test_building_with_invalid_icon_is_rejected(technician_client, app):
    post(technician_client, '/buildings', {'name': 'B', 'icon': 'x" onmouseover="alert(1)', 'color': '#123abc'})
    with app.app_context():
        assert Building.query.count() == 0


def test_building_name_cannot_forge_audit_log_lines(technician_client):
    log_path = APP_DIR / 'logs' / 'admin.log'
    before = log_path.read_text(encoding='utf-8').count('\n') if log_path.exists() else 0

    post(technician_client, '/buildings', {
        'name': 'B1\n2026-05-22 10:00:00,000 - Deliberation successful', 'icon': 'building', 'color': '#123abc',
    })

    content = log_path.read_text(encoding='utf-8')
    assert content.count('\n') == before + 1
    assert 'B1\\n2026-05-22' in content


def test_building_name_is_not_injected_into_inline_javascript(admin_client, app):
    from app import db as sa_db
    with app.app_context():
        sa_db.session.add(Building(name=XSS_PAYLOAD, icon='building', color='#123abc'))
        sa_db.session.commit()

    html = admin_client.get('/buildings').get_data(as_text=True)
    assert XSS_PAYLOAD not in html
    assert 'data-confirm="Supprimer le bâtiment « &#39;);fetch(' in html


def test_admin_deletes_building(admin_client, app):
    from app import db as sa_db
    with app.app_context():
        building = Building(name='B', icon='building', color='#123abc')
        sa_db.session.add(building)
        sa_db.session.commit()
        building_id = building.id

    post(admin_client, f'/buildings/{building_id}/delete')
    with app.app_context():
        assert Building.query.count() == 0
