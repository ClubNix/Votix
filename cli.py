from dotenv import load_dotenv

from app.blueprints import mail_sender, votix, auth
from app.models import Voter
from start import app

import csv
import uuid
import random
import click
import os


load_dotenv(dotenv_path='./app/.env')
_VALID_EMAIL_DOMAINS = os.getenv('VALID_EMAIL_DOMAINS').split(',')
_PROMOTION_LIST = os.getenv('PROMOTION_LIST').split(',')


@click.command(help="Print 'Hello World!'")
def hello():
    click.echo('Hello World!')


@click.command(help="Send invitation emails to voters")
def send_invitation():
    with app.app_context():
        voters = Voter.query.all()
        for voter in voters:
            if not voter.invitation_sent:
                print(f"Sending invitation email to {voter.email}")
                mail_sender.send_invitation_email(voter)


@click.command(help="Send voting link emails to voters")
def send_link():
    with app.app_context():
        voters = Voter.query.all()
        for voter in voters:
            if not voter.link_sent:
                print(f"Sending link email to {voter.email}")
                mail_sender.send_link_email(voter)


@click.command(help="Send reminder emails to voters")
def send_reminder():
    with app.app_context():
        voters = Voter.query.all()
        for voter in voters:
            if voter.link_sent:
                print(f"Sending reminder email to {voter.email}")
                mail_sender.send_reminder_email(voter)


@click.command(help="Send a test email to the admin")
def send_admin_test():
    print("Sending test email")
    mail_sender.send_admin_test_email()


@click.command(help="Import voters from a CSV file")
@click.option('--file',
              help='CSV file containing voters to import (last_name, first_name, email, promotion)')
def import_voters(file: str):
    with app.app_context():
        db = votix.DatabaseHandler('app/var/db.sqlite')
        try:
            with open(file, 'r') as f:
                reader = csv.reader(f)
                next(reader)
                for row in reader:
                    last_name, first_name, email, promotion = row
                    link_string = str(uuid.uuid4())
                    secret = str(random.randint(0, 9999)).zfill(4)
                    db.add_voter(Voter(
                        last_name=last_name, first_name=first_name, email=email, promotion=promotion,
                        link_string=link_string, secret=secret, voted=False, invitation_sent=False, link_sent=False)
                    )
                    print(f"Added voter {email}")
        except Exception as e:
            print(f"An error occurred while importing voters: {e}")
        db.close_connection()


@click.command(help="Validate a voters CSV file")
@click.option('--file', help='CSV file containing voters to validate')
def validate_voters_csv(file: str):
    with app.app_context():
        try:
            with open(file, 'r') as f:
                reader = csv.reader(f)
                next(reader)
                for row in reader:
                    last_name, first_name, email, promotion = row

                    if not mail_sender.validate_email_domain(email):
                        print(f"Invalid email domain for {email}")
                    if promotion not in _PROMOTION_LIST:
                        print(f"Invalid promotion for {email}")

        except Exception as e:
            print(f"An error occurred while validating voters: {e}")


@click.command(help="Add a admin user to the app")
@click.option('--role', help='Role of the user to add')
def add_user(role: str):
    with app.app_context():
        auth.create_user(role)


@click.command(help="Reset the app")
def reset_app():
    try:
        os.remove('app/var/db.sqlite')
        os.remove('app/var/pubkey.pem')
        print("App reset successfully")
    except Exception as e:
        print(f"An error occurred while resetting the app: {e}")


@click.group()
def cli():
    pass


cli.add_command(hello)
cli.add_command(send_invitation)
cli.add_command(send_link)
cli.add_command(send_reminder)
cli.add_command(send_admin_test)
cli.add_command(import_voters)
cli.add_command(validate_voters_csv)
cli.add_command(add_user)
cli.add_command(reset_app)

if __name__ == '__main__':
    cli()
