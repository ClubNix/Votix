from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText
from dotenv import load_dotenv
from email_validator import validate_email, EmailNotValidError
from jinja2 import Environment, FileSystemLoader
from flask import current_app

from app import db
from ..models import Voter

import os
import ssl
import logging
import smtplib


load_dotenv(dotenv_path='./app/.env')
_SMTP_SERVER = os.getenv("SMTP_SERVER")
_SMTP_PORT = int(os.getenv("SMTP_PORT"))
_SMTP_USERNAME = os.getenv("SMTP_USERNAME")
_SMTP_PASSWORD = os.getenv("SMTP_PASSWORD")
_SMTP_FROM = os.getenv("SMTP_FROM")
_SMTP_REPLY_TO = os.getenv("SMTP_REPLY_TO")
_SMTP_VERIFY_SSL = os.getenv("SMTP_VERIFY_SSL")
_VALID_EMAIL_DOMAINS = os.getenv('VALID_EMAIL_DOMAINS').split(',')

email_logger = logging.getLogger(__name__)
email_logger.setLevel(logging.INFO)
handler = logging.FileHandler('./app/logs/email.log')
handler.setLevel(logging.INFO)
formatter = logging.Formatter('%(asctime)s - %(message)s')
handler.setFormatter(formatter)
email_logger.addHandler(handler)

env = Environment(loader=FileSystemLoader('./app/templates/mails'))

smtp_ssl_context = ssl.create_default_context()
smtp_ssl_context.check_hostname = False
if _SMTP_VERIFY_SSL == "False":
    smtp_ssl_context.verify_mode = ssl.CERT_NONE


def check_email(email: str):
    """
    Check if email is valid
    :param email: Email to check
    :return: True if email is valid, error message if email is invalid
    """
    try:
        validate_email(email)
        return True
    except EmailNotValidError as e:
        return e


def validate_email_domain(email):
    try:
        v = validate_email(email)
        domain = v["domain"]
        if domain in _VALID_EMAIL_DOMAINS:
            return True
        else:
            return False
    except EmailNotValidError:
        return False


def send_email(subject: str, body: str, recipient: str):
    """
    Send an email
    :param subject: Email subject
    :param body: Email body
    :param recipient: Email recipient
    :return:
    """

    msg = MIMEMultipart()
    msg['From'] = _SMTP_FROM
    msg['Reply-To'] = _SMTP_REPLY_TO
    msg['To'] = recipient
    msg['Subject'] = subject
    msg.attach(MIMEText(body, 'plain'))

    try:
        with smtplib.SMTP_SSL(_SMTP_SERVER, _SMTP_PORT, context=smtp_ssl_context) as server:
#            server.starttls(context=smtp_ssl_context)
            server.login(_SMTP_USERNAME, _SMTP_PASSWORD)
            server.sendmail(_SMTP_FROM, recipient, msg.as_string())
    except smtplib.SMTPException as e:
        logging.error(e)


def send_invitation_email(voter: Voter):
    """
    Send the vote invitation email
    :param voter: Voter to send email to
    :return:
    """
    subject = f"{voter.first_name}, you have been invited to vote"
    msg_data = {'first_name': voter.first_name}
    html_content = env.get_template('invitation.html').render(msg_data)
    send_email(subject, html_content, voter.email)
    email_logger.info(f"Invitation email sent to {voter.email}")
    voter.invitation_sent = True
    db.session.commit()


def send_link_email(voter: Voter):
    """
    Send the voting link email
    :param voter: Voter to send email to
    :return:
    """
    subject = f"{voter.first_name}, your voting link"
    msg_data = {'first_name': voter.first_name,
                'voting_link': f"{current_app.config['VOTING_URL']}/{voter.link_string}",
                'secret_code': voter.secret}
    html_content = env.get_template('send-link.html').render(msg_data)
    send_email(subject, html_content, voter.email)
    email_logger.info(f"Link email sent to {voter.email}")
    voter.link_sent = True
    db.session.commit()


def send_reminder_email(voter: Voter):
    """
    Send the voting reminder email
    :param voter: Voter to send email to
    :return:
    """
    subject = f"{voter.first_name}, it's time to vote"
    msg_data = {'first_name': voter.first_name,
                'voting_link': f"{current_app.config['VOTING_URL']}/{voter.link_string}",
                'secret_code': voter.secret}
    html_content = env.get_template('reminder.html').render(msg_data)
    send_email(subject, html_content, voter.email)
    email_logger.info(f"Reminder email sent to {voter.email}")


def send_admin_test_email():
    """
    Send a test email to the admin
    :return:
    """
    subject = "Test email"
    html_content = env.get_template('admin-test.html').render()
    send_email(subject, html_content, os.getenv("ADMIN_EMAIL"))
    email_logger.info(f"Test email sent to {os.getenv('ADMIN_EMAIL')}")
