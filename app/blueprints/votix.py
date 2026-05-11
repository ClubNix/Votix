from flask import Blueprint, render_template, request, flash, current_app
from flask_login import login_required
from dotenv import load_dotenv, dotenv_values

from .database import DatabaseHandler
from .mail_sender import validate_email_domain, send_link_email
from .auth import admin_required, technician_required
from ..models import Voter, Candidate
from .crypto import encrypt_ballot

import uuid
import random
import logging
import csv
import os
import time


votix = Blueprint('votix', __name__)

load_dotenv(dotenv_path='./app/.env')
_PROMOTION_LIST = os.getenv('PROMOTION_LIST').split(',')

promotion_list = [element for element in _PROMOTION_LIST if element != '']

votix_logger = logging.getLogger(__name__)
votix_logger.setLevel(logging.INFO)
handler = logging.FileHandler('./app/logs/votix.log')
handler.setLevel(logging.INFO)
formatter = logging.Formatter('%(asctime)s - %(message)s')
handler.setFormatter(formatter)
votix_logger.addHandler(handler)


@votix.route('/register-voter', methods=['GET', 'POST'])
@login_required
@technician_required
def register_voter():
    if request.method == 'POST':
        last_name = request.form['last_name'].lower()
        first_name = request.form['first_name'].lower()
        email = request.form['email']
        promotion = request.form['promotion']

        send_email = bool(request.form.get('send_email'))

        if not validate_email_domain(email):
            flash('Adresse email invalide ou domaine non autorisé.', 'danger')
            return render_template('register_voter.html', list=promotion_list)

        with DatabaseHandler('app/var/db.sqlite') as db:
            if db.get_voter_by_email(email) is not None:
                flash('Un électeur avec cette adresse email existe déjà.', 'danger')
                return render_template('register_voter.html', list=promotion_list)

            try:
                link_string = str(uuid.uuid4())
                secret = str(random.randint(0, 9999)).zfill(4)
                db.add_voter(Voter(last_name=last_name, first_name=first_name, email=email, promotion=promotion,
                                   voted=False, link_string=link_string, secret=secret, invitation_sent=False,
                                   link_sent=False))
            except Exception as e:
                flash(f'Une erreur est survenue lors de l\'ajout de l\'électeur : {e}', 'danger')
                return render_template('register_voter.html', list=promotion_list)

        if not send_email:
            flash('Électeur ajouté. Le lien de vote n\'a pas été envoyé.', 'success')
        else:
            try:
                voter_obj = Voter.query.filter_by(email=email).first()
                if voter_obj:
                    send_link_email(voter_obj)
                    flash('Électeur ajouté et lien de vote envoyé avec succès.', 'success')
                else:
                    flash('Électeur ajouté, mais impossible de récupérer le compte pour l\'envoi du lien.', 'warning')
            except Exception as e:
                votix_logger.error(f"Failed to send link email to {email}: {e}")
                flash('Électeur ajouté, mais l\'envoi du lien de vote a échoué.', 'warning')

        return render_template('register_voter.html', list=promotion_list)
    else:
        return render_template('register_voter.html', list=promotion_list)


@votix.route('/import-voters', methods=['GET', 'POST'])
@login_required
@admin_required
def import_voters():
    if request.method == 'POST':
        file = request.files['file']
        if file.filename == '':
            flash('Aucun fichier sélectionné.', 'danger')
            return render_template('import_voters.html', list=promotion_list)

        if file:
            try:
                filepath = os.path.join(current_app.config['FILE_UPLOADS'], f'{uuid.uuid4()}.csv')
                file.save(filepath)
                with DatabaseHandler('app/var/db.sqlite') as db:
                    with open(filepath, 'r') as f:
                        reader = csv.reader(f)
                        next(reader)
                        for row in reader:
                            last_name, first_name, email, promotion = row

                            link_string = str(uuid.uuid4())
                            secret = str(random.randint(0, 9999)).zfill(4)
                            db.add_voter(Voter(
                                last_name=last_name, first_name=first_name, email=email, promotion=promotion,
                                link_string=link_string, secret=secret)
                            )
            except Exception as e:
                flash('Une erreur est survenue lors de l\'importation des électeurs.', 'danger')
                votix_logger.error(f"An error occurred while importing voters: {e}")
                return render_template('import_voters.html', list=promotion_list)

            votix_logger.info('Voters imported successfully via {file.filename}')
            flash('Électeurs importés avec succès.', 'success')
            return render_template('import_voters.html', list=promotion_list)
    else:
        return render_template('import_voters.html', list=promotion_list)


@votix.route('/voters')
@login_required
@technician_required
def voters_list():
    from ..models import Voter as VoterModel
    all_voters = VoterModel.query.order_by(VoterModel.last_name, VoterModel.first_name).all()
    safe_voters = [
        {
            'last_name':       v.last_name,
            'first_name':      v.first_name,
            'email':           v.email,
            'promotion':       v.promotion,
            'invitation_sent': v.invitation_sent,
            'link_sent':       v.link_sent,
        }
        for v in all_voters
    ]
    return render_template('voters.html', voters=safe_voters)


@votix.route('/candidates')
@login_required
@technician_required
def candidates():
    with DatabaseHandler('app/var/db.sqlite') as db:
        all_candidates = db.get_candidates()
    return render_template('candidates.html', candidates=all_candidates)


@votix.route('/register-candidate', methods=['GET', 'POST'])
@login_required
@technician_required
def register_candidate():
    if request.method == 'POST':
        name = request.form['name']
        eligible = True if request.form.get('eligible') else False

        try:
            with DatabaseHandler('app/var/db.sqlite') as db:
                db.add_candidate(Candidate(name=name, eligible=eligible))
        except Exception as e:
            flash(f'Une erreur est survenue lors de l\'ajout du candidat : {e}', 'danger')
            return render_template('register_candidate.html')

        flash('Candidat ajouté avec succès.', 'success')
        return render_template('register_candidate.html')
    else:
        return render_template('register_candidate.html')


@votix.route('/vote/<link_string>', methods=['GET', 'POST'])
def vote(link_string):
    current_time = int(time.time())
    cfg = dotenv_values('./app/.env')
    voting_start = int(cfg.get('VOTING_START', 0) or 0)
    voting_end   = int(cfg.get('VOTING_END', 0) or 0)

    if current_time < voting_start:
        flash("Le vote n'a pas encore commencé.", 'danger')
        return render_template('index.html')
    if current_time > voting_end:
        flash('Le vote est terminé.', 'danger')
        return render_template('index.html')

    with DatabaseHandler('app/var/db.sqlite') as db:
        voter = db.get_voter_by_link(link_string)
        candidates = db.get_candidates()

        if voter is None:
            flash("Ce lien de vote n'existe pas.", 'danger')
            return render_template('index.html')
        if voter.voted:
            flash('Cet électeur a déjà voté.', 'danger')
            return render_template('index.html')

        if request.method == 'POST':
            candidate_id = int(request.form['candidate'])
            secret_code = request.form['secret']

            if not candidate_id:
                flash('Veuillez sélectionner un candidat.', 'danger')
                return render_template('vote.html', voter=voter, candidates=candidates)
            if db.get_eligible_candidate(candidate_id) is None:
                flash('Candidat invalide ou non éligible.', 'danger')
                return render_template('vote.html', voter=voter, candidates=candidates)

            if not secret_code:
                flash('Le code secret est requis.', 'danger')
                return render_template('vote.html', voter=voter, candidates=candidates)
            if voter.secret != secret_code:
                flash('Code secret invalide.', 'danger')
                return render_template('vote.html', voter=voter, candidates=candidates)

            try:
                pubkey = open('app/var/pubkey.pem', 'rb').read()
                ballot = encrypt_ballot(str(candidate_id), pubkey, str(voter.link_string))
                db.add_vote(voter, ballot)
            except Exception as e:
                flash(f'Une erreur est survenue lors de l\'enregistrement du vote : {e}', 'danger')
                return render_template('vote.html', voter=voter, candidates=candidates)

        else:
            return render_template('vote.html', voter=voter, candidates=candidates)

    flash('Vote enregistré avec succès.', 'success')
    return render_template('index.html')
