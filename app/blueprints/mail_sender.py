from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText
from email.mime.image import MIMEImage
from dotenv import load_dotenv, dotenv_values
from email_validator import validate_email, EmailNotValidError
from jinja2 import Environment, FileSystemLoader
from flask import current_app
from datetime import datetime
from zoneinfo import ZoneInfo

from app import db
from ..models import Voter, User

import os
import ssl
import logging
import smtplib


load_dotenv(dotenv_path='./app/.env')

PARIS_TZ = ZoneInfo('Europe/Paris')


def _fmt_vote_date(ts) -> str:
    try:
        return datetime.fromtimestamp(int(ts), tz=PARIS_TZ).strftime('%d/%m/%Y à %Hh%M')
    except Exception:
        return '—'

email_logger = logging.getLogger(__name__)
email_logger.setLevel(logging.INFO)
handler = logging.FileHandler('./app/logs/email.log')
handler.setLevel(logging.INFO)
formatter = logging.Formatter('%(asctime)s - %(message)s')
handler.setFormatter(formatter)
email_logger.addHandler(handler)

env = Environment(loader=FileSystemLoader('./app/templates/mails'))


def check_email(email: str):
    try:
        validate_email(email)
        return True
    except EmailNotValidError as e:
        return e


DOTENV_PATH = './app/.env'


def validate_email_domain(email):
    try:
        v = validate_email(email)
        domain = v["domain"]
        cfg = dotenv_values(DOTENV_PATH)
        raw = cfg.get('VALID_EMAIL_DOMAINS', '')
        valid_domains = [d.strip() for d in raw.split(',') if d.strip()]
        return domain in valid_domains
    except EmailNotValidError:
        return False


LOGO_PATH = './app/static/images/votix.png'
LOGO_CID  = 'votix_logo'


def send_email(subject: str, body: str, recipient: str, mime: str = 'html'):
    cfg = dotenv_values(DOTENV_PATH)
    smtp_server   = cfg.get('SMTP_SERVER', '')
    smtp_port     = cfg.get('SMTP_PORT', '465')
    smtp_username = cfg.get('SMTP_USERNAME', '')
    smtp_password = cfg.get('SMTP_PASSWORD', '')
    smtp_from     = cfg.get('SMTP_FROM', '')
    smtp_reply_to = cfg.get('SMTP_REPLY_TO', '')
    smtp_verify_ssl = cfg.get('SMTP_VERIFY_SSL', 'True')

    ctx = ssl.create_default_context()
    ctx.check_hostname = False
    if str(smtp_verify_ssl) == 'False':
        ctx.verify_mode = ssl.CERT_NONE

    if mime == 'html':
        # Wrap in multipart/related so we can embed the logo as a CID inline image
        msg = MIMEMultipart('related')
        msg.attach(MIMEText(body, 'html'))
        try:
            with open(LOGO_PATH, 'rb') as f:
                logo = MIMEImage(f.read())
            logo.add_header('Content-ID', f'<{LOGO_CID}>')
            logo.add_header('Content-Disposition', 'inline', filename='votix.png')
            msg.attach(logo)
        except FileNotFoundError:
            pass
    else:
        msg = MIMEMultipart()
        msg.attach(MIMEText(body, mime))

    msg['From']     = smtp_from
    msg['Reply-To'] = smtp_reply_to
    msg['To']       = recipient
    msg['Subject']  = subject

    try:
        with smtplib.SMTP_SSL(smtp_server, int(smtp_port), context=ctx) as server:
            server.login(smtp_username, smtp_password)
            server.sendmail(smtp_from, recipient, msg.as_string())
    except smtplib.SMTPException as e:
        email_logger.error(e)
        raise


def send_invitation_email(voter: Voter):
    cfg = dotenv_values(DOTENV_PATH)
    subject = f"Bonjour {voter.first_name}, vous avez été invité(e) à voter"
    html_content = env.get_template('invitation.html').render({
        'first_name':   voter.first_name,
        'voting_start': _fmt_vote_date(cfg.get('VOTING_START', 0)),
        'voting_end':   _fmt_vote_date(cfg.get('VOTING_END', 0)),
    })
    send_email(subject, html_content, voter.email)
    email_logger.info(f"Invitation email sent to {voter.email}")
    voter.invitation_sent = True
    db.session.commit()


def send_link_email(voter: Voter):
    cfg = dotenv_values(DOTENV_PATH)
    subject = f"{voter.first_name}, votre lien de vote"
    html_content = env.get_template('send-link.html').render({
        'first_name':   voter.first_name,
        'voting_link':  f"{cfg.get('VOTING_URL', '')}/{voter.link_string}",
        'secret_code':  voter.secret,
        'voting_start': _fmt_vote_date(cfg.get('VOTING_START', 0)),
        'voting_end':   _fmt_vote_date(cfg.get('VOTING_END', 0)),
    })
    send_email(subject, html_content, voter.email)
    email_logger.info(f"Link email sent to {voter.email}")
    voter.link_sent = True
    db.session.commit()


def send_reminder_email(voter: Voter):
    cfg = dotenv_values(DOTENV_PATH)
    subject = f"{voter.first_name}, il est temps de voter !"
    html_content = env.get_template('reminder.html').render({
        'first_name':  voter.first_name,
        'voting_link': f"{cfg.get('VOTING_URL', '')}/{voter.link_string}",
        'secret_code': voter.secret,
        'voting_end':  _fmt_vote_date(cfg.get('VOTING_END', 0)),
    })
    send_email(subject, html_content, voter.email)
    email_logger.info(f"Reminder email sent to {voter.email}")


def send_admin_test_email():
    cfg = dotenv_values('./app/.env')

    def _fmt_ts(ts):
        try:
            readable = datetime.fromtimestamp(int(ts), tz=PARIS_TZ).strftime('%d/%m/%Y %H:%M') + ' (Europe/Paris)'
            return f"{readable} [{ts}]"
        except Exception:
            return str(ts)

    smtp_pwd_raw = cfg.get('SMTP_PASSWORD', '')
    smtp_pwd_masked = smtp_pwd_raw[:3] + '*' * max(0, len(smtp_pwd_raw) - 3) if smtp_pwd_raw else ''

    valid_domains_raw = cfg.get('VALID_EMAIL_DOMAINS', '')
    valid_domains_list = [d.strip() for d in valid_domains_raw.split(',') if d.strip()]

    ctx = {
        'voting_start':   _fmt_ts(cfg.get('VOTING_START', 0)),
        'voting_end':     _fmt_ts(cfg.get('VOTING_END', 0)),
        'smtp_server':    cfg.get('SMTP_SERVER', ''),
        'smtp_port':      cfg.get('SMTP_PORT', ''),
        'smtp_username':  cfg.get('SMTP_USERNAME', ''),
        'smtp_password':  smtp_pwd_masked,
        'smtp_from':      cfg.get('SMTP_FROM', ''),
        'smtp_reply_to':  cfg.get('SMTP_REPLY_TO', ''),
        'smtp_verify_ssl': cfg.get('SMTP_VERIFY_SSL', ''),
        'voting_url':     cfg.get('VOTING_URL', ''),
        'valid_domains':  ', '.join(valid_domains_list),
        'admin_email':    cfg.get('ADMIN_EMAIL', ''),
        'sent_at':        datetime.now(tz=PARIS_TZ).strftime('%d/%m/%Y %H:%M:%S'),
    }

    subject = "[Votix] Email de test — récapitulatif de configuration"
    content = env.get_template('admin-test.html').render(ctx)
    admin = User.query.filter_by(role='admin').first()
    admin_email = admin.email if admin else ''
    send_email(subject, content, admin_email, mime='plain')
    email_logger.info(f"Test email sent to {admin_email}")
