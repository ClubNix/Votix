from dotenv import dotenv_values

from app.blueprints import mail_sender, auth
from app.blueprints.database import DatabaseHandler
from app.models import Voter
from start import app

import csv
import uuid
import random
import click
import os


DOTENV_PATH = './app/.env'


def _cfg():
    return dotenv_values(DOTENV_PATH)


def _promotion_list():
    raw = _cfg().get('PROMOTION_LIST', '')
    return [p.strip() for p in raw.split(',') if p.strip()]


# ── Email commands ─────────────────────────────────────────────────────────────

@click.command(help="Send invitation emails to voters who haven't received one yet")
def send_invitations():
    with app.app_context():
        voters = Voter.query.all()
        pending = [v for v in voters if not v.invitation_sent]
        if not pending:
            click.echo('No pending invitations.')
            return
        ok, fail = 0, 0
        for voter in pending:
            try:
                mail_sender.send_invitation_email(voter)
                click.echo(f'  ✓ {voter.email}')
                ok += 1
            except Exception as e:
                click.echo(f'  ✗ {voter.email}: {e}', err=True)
                fail += 1
        click.echo(f'\n{ok} sent, {fail} failed.')


@click.command(help="Send voting link emails to voters who haven't received one yet")
def send_links():
    with app.app_context():
        voters = Voter.query.all()
        pending = [v for v in voters if not v.link_sent]
        if not pending:
            click.echo('No pending links.')
            return
        ok, fail = 0, 0
        for voter in pending:
            try:
                mail_sender.send_link_email(voter)
                click.echo(f'  ✓ {voter.email}')
                ok += 1
            except Exception as e:
                click.echo(f'  ✗ {voter.email}: {e}', err=True)
                fail += 1
        click.echo(f'\n{ok} sent, {fail} failed.')


@click.command(help="Send reminder emails to voters who haven't voted yet")
def send_reminders():
    with app.app_context():
        voters = Voter.query.filter_by(voted=False).all()
        if not voters:
            click.echo('No voters to remind (all have voted or none registered).')
            return
        ok, fail = 0, 0
        for voter in voters:
            try:
                mail_sender.send_reminder_email(voter)
                click.echo(f'  ✓ {voter.email}')
                ok += 1
            except Exception as e:
                click.echo(f'  ✗ {voter.email}: {e}', err=True)
                fail += 1
        click.echo(f'\n{ok} sent, {fail} failed.')


@click.command(help="Send a test email to the admin with the current configuration")
def send_admin_test():
    with app.app_context():
        try:
            mail_sender.send_admin_test_email()
            click.echo(f"Test email sent to {_cfg().get('ADMIN_EMAIL', '(admin)')}.")
        except Exception as e:
            click.echo(f'Failed: {e}', err=True)


# ── Voter commands ─────────────────────────────────────────────────────────────

@click.command(help="Import voters from a CSV file (columns: last_name, first_name, email, promotion)")
@click.option('--file', required=True, help='Path to the CSV file')
@click.option('--send-link', is_flag=True, default=False, help='Send voting link email to each imported voter')
def import_voters(file, send_link):
    with app.app_context():
        db = DatabaseHandler('app/var/db.sqlite')
        ok, fail = 0, 0
        try:
            with open(file, 'r') as f:
                reader = csv.reader(f)
                next(reader)
                for row in reader:
                    try:
                        last_name, first_name, email, promotion = row
                        link_string = str(uuid.uuid4())
                        secret = str(random.randint(0, 9999)).zfill(4)
                        db.add_voter(Voter(
                            last_name=last_name, first_name=first_name,
                            email=email, promotion=promotion,
                            link_string=link_string, secret=secret,
                            voted=False, invitation_sent=False, link_sent=False,
                        ))
                        click.echo(f'  ✓ Added {email}')
                        ok += 1

                        if send_link:
                            voter_obj = Voter.query.filter_by(email=email).first()
                            if voter_obj:
                                mail_sender.send_link_email(voter_obj)
                                click.echo(f'    → Link sent to {email}')
                    except Exception as e:
                        click.echo(f'  ✗ Row {row}: {e}', err=True)
                        fail += 1
        except FileNotFoundError:
            click.echo(f'File not found: {file}', err=True)
            return
        db.close_connection()
        click.echo(f'\n{ok} imported, {fail} failed.')


@click.command(help="Validate a voters CSV file (checks email domains and promotions)")
@click.option('--file', required=True, help='Path to the CSV file')
def validate_csv(file):
    with app.app_context():
        promotions = _promotion_list()
        errors = 0
        try:
            with open(file, 'r') as f:
                reader = csv.reader(f)
                next(reader)
                for i, row in enumerate(reader, start=2):
                    try:
                        last_name, first_name, email, promotion = row
                    except ValueError:
                        click.echo(f'  Line {i}: bad row format — {row}', err=True)
                        errors += 1
                        continue

                    if not mail_sender.validate_email_domain(email):
                        click.echo(f'  Line {i}: invalid email or domain — {email}')
                        errors += 1
                    if promotions and promotion.strip() not in promotions:
                        click.echo(f'  Line {i}: unknown promotion "{promotion}" for {email}')
                        errors += 1

        except FileNotFoundError:
            click.echo(f'File not found: {file}', err=True)
            return

        if errors:
            click.echo(f'\n{errors} error(s) found.')
        else:
            click.echo('CSV is valid.')


# ── User commands ──────────────────────────────────────────────────────────────

@click.command(help="Create a system user (admin or technician)")
@click.option('--role', required=True, type=click.Choice(['admin', 'technician']), help='Role of the new user')
def create_user(role):
    with app.app_context():
        auth.create_user(role)


# ── Maintenance commands ───────────────────────────────────────────────────────

@click.command(help="Reset the application: wipe the database and RSA keys")
@click.confirmation_option(prompt='This will wipe the entire database. Are you sure?')
def reset():
    removed, missing = [], []
    for path in ('app/var/db.sqlite', 'app/var/pubkey.pem', 'app/var/privkey.pem'):
        try:
            os.remove(path)
            removed.append(path)
        except FileNotFoundError:
            missing.append(path)

    for p in removed:
        click.echo(f'  Deleted {p}')
    for p in missing:
        click.echo(f'  Not found (skipped): {p}')
    click.echo('Reset complete.')


# ── CLI group ─────────────────────────────────────────────────────────────────

@click.group()
def cli():
    pass


cli.add_command(send_invitations)
cli.add_command(send_links)
cli.add_command(send_reminders)
cli.add_command(send_admin_test)
cli.add_command(import_voters)
cli.add_command(validate_csv)
cli.add_command(create_user)
cli.add_command(reset)

if __name__ == '__main__':
    cli()
