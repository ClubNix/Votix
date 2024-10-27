from flask import Blueprint, render_template, request, flash, current_app
from flask_login import login_required
from datetime import datetime
from dotenv import load_dotenv

from .database import DatabaseHandler
from .mail_sender import validate_email_domain
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
_VOTING_START = int(os.getenv('VOTING_START'))
_VOTING_END = int(os.getenv('VOTING_END'))

promotion_list = [element for element in _PROMOTION_LIST if element != '']
promotion_list = [str(datetime.now().year - 1)[-2:] + "_" + element for element in promotion_list]

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

        if not validate_email_domain(email):
            flash('Invalid email address', 'danger')
            return render_template('register_voter.html', list=promotion_list)

        db = DatabaseHandler('app/var/db.sqlite')
        if db.get_voter_by_email(email) is not None:
            flash('A voter with this email already exists', 'danger')
            return render_template('register_voter.html', list=promotion_list)

        try:
            link_string = str(uuid.uuid4())
            secret = str(random.randint(0, 9999)).zfill(4)
            db.add_voter(Voter(last_name=last_name, first_name=first_name, email=email, promotion=promotion,
                               voted=False, link_string=link_string, secret=secret, invitation_sent=False,
                               link_sent=False))
        except Exception as e:
            flash(f'An error occurred while adding the voter: {e}', 'danger')
            return render_template('register_voter.html', list=promotion_list)

        db.close_connection()

        flash('Voter added successfully', 'success')
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
            flash('No selected file', 'danger')
            return render_template('import_voters.html', list=promotion_list)

        if file:
            db = DatabaseHandler('app/var/db.sqlite')
            try:
                filepath = os.path.join(current_app.config['FILE_UPLOADS'], f'{uuid.uuid4()}.csv')
                file.save(filepath)
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
                flash('An error occurred while importing voters', 'danger')
                votix_logger.error(f"An error occurred while importing voters: {e}")
                return render_template('import_voters.html', list=promotion_list)
            db.close_connection()

            votix_logger.info('Voters imported successfully via {file.filename}')
            flash('Voters imported successfully', 'success')
            return render_template('import_voters.html', list=promotion_list)
    else:
        return render_template('import_voters.html', list=promotion_list)


@votix.route('/register-candidate', methods=['GET', 'POST'])
@login_required
@technician_required
def register_candidate():
    if request.method == 'POST':
        name = request.form['name']
        eligible = True if request.form.get('eligible') else False

        db = DatabaseHandler('app/var/db.sqlite')
        try:
            db.add_candidate(Candidate(name=name, eligible=eligible))
        except Exception as e:
            flash(f'An error occurred while adding the candidate: {e}', 'danger')
            return render_template('register_candidate.html')
        db.close_connection()

        flash('Candidate added successfully', 'success')
        return render_template('register_candidate.html')
    else:
        return render_template('register_candidate.html')


@votix.route('/vote/<link_string>', methods=['GET', 'POST'])
def vote(link_string):
    db = DatabaseHandler('app/var/db.sqlite')
    voter = db.get_voter_by_link(link_string)
    candidates = db.get_candidates()

    current_time = int(time.time())

    if current_time < _VOTING_START:
        flash('Voting has not started yet', 'danger')
        return render_template('index.html')
    if current_time > _VOTING_END:
        flash('Voting has ended', 'danger')
        return render_template('index.html')

    if voter is None:
        flash("This vote link don't exist", 'danger')
        return render_template('index.html')
    if voter.voted:
        flash('This voter has already voted', 'danger')
        return render_template('index.html')

    if request.method == 'POST':
        candidate_id = int(request.form['candidate'])
        secret_code = request.form['secret']

        if not candidate_id:
            flash('Candidate is required', 'danger')
            return render_template('vote.html', voter=voter, candidates=candidates)
        if db.get_candidate(candidate_id) is None:
            flash('Invalid candidate', 'danger')
            return render_template('vote.html', voter=voter, candidates=candidates)

        if not secret_code:
            flash('Secret code is required', 'danger')
            return render_template('vote.html', voter=voter, candidates=candidates)
        if voter.secret != secret_code:
            flash('Invalid secret code', 'danger')
            return render_template('vote.html', voter=voter, candidates=candidates)

        try:
            pubkey = open('app/var/pubkey.pem', 'rb').read()
            ballot = encrypt_ballot(str(candidate_id), pubkey, str(voter.link_string))
            db.add_vote(voter, ballot)
        except Exception as e:
            flash(f'An error occurred while registering the vote: {e}', 'danger')
            return render_template('vote.html', voter=voter, candidates=candidates)

        db.close_connection()

        flash('Vote registered successfully', 'success')
        return render_template('index.html')
    else:
        return render_template('vote.html', voter=voter, candidates=candidates)
