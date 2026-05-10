from flask import Blueprint, render_template, request, flash, redirect, url_for, current_app
from flask_login import login_required
from datetime import datetime
from zoneinfo import ZoneInfo
from dotenv import set_key, dotenv_values

from .auth import admin_required
from .mail_sender import send_invitation_email, send_link_email, send_reminder_email, send_admin_test_email
from ..models import Voter

import os
import logging

PARIS_TZ   = ZoneInfo('Europe/Paris')
DOTENV_PATH = './app/.env'

configure_bp = Blueprint('configure', __name__)

configure_logger = logging.getLogger(__name__)
configure_logger.setLevel(logging.INFO)
_handler = logging.FileHandler('./app/logs/admin.log')
_handler.setLevel(logging.INFO)
_handler.setFormatter(logging.Formatter('%(asctime)s - %(message)s'))
configure_logger.addHandler(_handler)


def _save(key: str, value: str):
    """Write to .env, os.environ, and app.config in one shot."""
    set_key(DOTENV_PATH, key, value)
    os.environ[key] = value
    current_app.config[key] = value


def _env() -> dict:
    """Read the .env file fresh — works across gunicorn workers."""
    return dotenv_values(DOTENV_PATH)


def _fmt_ts(ts) -> str:
    try:
        return datetime.fromtimestamp(int(ts), tz=PARIS_TZ).strftime('%Y-%m-%dT%H:%M')
    except Exception:
        return ''


# ── Main configure route ───────────────────────────────────────────────────

@configure_bp.route('/configure', methods=['GET', 'POST'])
@login_required
@admin_required
def configure():
    if request.method == 'POST':
        section = request.form.get('section')

        if section == 'voting':
            try:
                start_str = request.form['voting_start']
                end_str   = request.form['voting_end']
                start_ts  = int(datetime.strptime(start_str, '%Y-%m-%dT%H:%M').replace(tzinfo=PARIS_TZ).timestamp())
                end_ts    = int(datetime.strptime(end_str,   '%Y-%m-%dT%H:%M').replace(tzinfo=PARIS_TZ).timestamp())
                _save('VOTING_START', str(start_ts))
                _save('VOTING_END',   str(end_ts))
                # Re-cast to int for the routes that read from app.config
                current_app.config['VOTING_START'] = start_ts
                current_app.config['VOTING_END']   = end_ts
                configure_logger.info(f"Voting period updated: {start_str} → {end_str}")
                flash('Période de vote mise à jour avec succès.', 'success')
            except Exception as e:
                flash(f'Erreur : {e}', 'danger')

        elif section == 'smtp':
            try:
                fields = {
                    'SMTP_SERVER':     request.form.get('smtp_server', ''),
                    'SMTP_PORT':       request.form.get('smtp_port', '465'),
                    'SMTP_USERNAME':   request.form.get('smtp_username', ''),
                    'SMTP_PASSWORD':   request.form.get('smtp_password', ''),
                    'SMTP_FROM':       request.form.get('smtp_from', ''),
                    'SMTP_REPLY_TO':   request.form.get('smtp_reply_to', ''),
                    'SMTP_VERIFY_SSL': 'True' if request.form.get('smtp_verify_ssl') else 'False',
                }
                for key, value in fields.items():
                    _save(key, value)
                current_app.config['SMTP_PORT'] = int(fields['SMTP_PORT'])
                configure_logger.info('SMTP configuration updated')
                flash('Configuration SMTP mise à jour avec succès.', 'success')
            except Exception as e:
                flash(f'Erreur : {e}', 'danger')

        elif section == 'url':
            try:
                voting_url = request.form.get('voting_url', '').rstrip('/')
                _save('VOTING_URL', voting_url)
                configure_logger.info(f"Voting URL updated: {voting_url}")
                flash('URL de vote mise à jour avec succès.', 'success')
            except Exception as e:
                flash(f'Erreur : {e}', 'danger')

        elif section == 'domains':
            try:
                raw     = request.form.get('valid_domains', '')
                domains = [d.strip() for d in raw.split(',') if d.strip()]
                _save('VALID_EMAIL_DOMAINS', ','.join(domains))
                current_app.config['VALID_EMAIL_DOMAINS'] = domains
                configure_logger.info(f"Valid email domains updated: {domains}")
                flash('Domaines email mis à jour avec succès.', 'success')
            except Exception as e:
                flash(f'Erreur : {e}', 'danger')

        tab = request.form.get('tab', '0')
        return redirect(url_for('configure.configure') + f'?tab={tab}')

    # GET — read fresh from .env so any worker sees the current state
    cfg = _env()

    ctx = {
        'voting_start':    _fmt_ts(cfg.get('VOTING_START', 0)),
        'voting_end':      _fmt_ts(cfg.get('VOTING_END', 0)),
        'smtp_server':     cfg.get('SMTP_SERVER', ''),
        'smtp_port':       cfg.get('SMTP_PORT', '465'),
        'smtp_username':   cfg.get('SMTP_USERNAME', ''),
        'smtp_password':   cfg.get('SMTP_PASSWORD', ''),
        'smtp_from':       cfg.get('SMTP_FROM', ''),
        'smtp_reply_to':   cfg.get('SMTP_REPLY_TO', ''),
        'smtp_verify_ssl': cfg.get('SMTP_VERIFY_SSL', 'True'),
        'voting_url':      cfg.get('VOTING_URL', ''),
        'valid_domains':   cfg.get('VALID_EMAIL_DOMAINS', ''),
        'admin_email':     cfg.get('ADMIN_EMAIL', ''),
    }
    return render_template('configure.html', **ctx)


# ── Email bulk actions ─────────────────────────────────────────────────────

@configure_bp.route('/send-test-email', methods=['POST'])
@login_required
@admin_required
def send_test_email():
    try:
        send_admin_test_email()
        flash(f"Email de test envoyé à {current_app.config.get('ADMIN_EMAIL', 'l\'admin')}.", 'success')
    except Exception as e:
        flash(f"Erreur lors de l'envoi : {e}", 'danger')
    return redirect(url_for('configure.configure') + '?tab=3')


@configure_bp.route('/send-invitations', methods=['POST'])
@login_required
@admin_required
def send_invitations():
    voters = Voter.query.all()
    count, errors = 0, 0
    for voter in voters:
        try:
            send_invitation_email(voter)
            count += 1
        except Exception as e:
            configure_logger.error(f"Failed to send invitation to {voter.email}: {e}")
            errors += 1
    if errors:
        flash(f'{count} invitation(s) envoyée(s), {errors} échec(s).', 'warning')
    else:
        flash(f'{count} invitation(s) envoyée(s) avec succès.', 'success')
    return redirect(url_for('configure.configure') + '?tab=3')


@configure_bp.route('/send-links', methods=['POST'])
@login_required
@admin_required
def send_links():
    voters = Voter.query.all()
    count, errors = 0, 0
    for voter in voters:
        try:
            send_link_email(voter)
            count += 1
        except Exception as e:
            configure_logger.error(f"Failed to send link to {voter.email}: {e}")
            errors += 1
    if errors:
        flash(f'{count} lien(s) de vote envoyé(s), {errors} échec(s).', 'warning')
    else:
        flash(f'{count} lien(s) de vote envoyé(s) avec succès.', 'success')
    return redirect(url_for('configure.configure') + '?tab=3')


@configure_bp.route('/send-reminders', methods=['POST'])
@login_required
@admin_required
def send_reminders():
    voters = Voter.query.filter_by(voted=False).all()
    count, errors = 0, 0
    for voter in voters:
        try:
            send_reminder_email(voter)
            count += 1
        except Exception as e:
            configure_logger.error(f"Failed to send reminder to {voter.email}: {e}")
            errors += 1
    if errors:
        flash(f'{count} rappel(s) envoyé(s), {errors} échec(s).', 'warning')
    else:
        flash(f'{count} rappel(s) envoyé(s) aux électeurs n\'ayant pas encore voté.', 'success')
    return redirect(url_for('configure.configure') + '?tab=3')
