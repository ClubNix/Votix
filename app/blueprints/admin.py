from flask import Blueprint, render_template, request, after_this_request, send_file, flash, current_app
from flask_login import login_required

from .auth import technician_required, admin_required
from .votix import DatabaseHandler
from .crypto import generate_rsa_keys, decrypt_ballot

import os
import uuid
import logging


admin = Blueprint('admin', __name__)

admin_logger = logging.getLogger(__name__)
admin_logger.setLevel(logging.INFO)
handler = logging.FileHandler('./app/logs/admin.log')
handler.setLevel(logging.INFO)
formatter = logging.Formatter('%(asctime)s - %(message)s')
handler.setFormatter(formatter)
admin_logger.addHandler(handler)


@admin.route('/dashboard', methods=['GET', 'POST'])
@login_required
@technician_required
def dashboard():
    db = DatabaseHandler('app/var/db.sqlite')
    voters = db.count_voters()[0]
    candidates = db.get_candidates()
    votes = db.count_votes()[0]

    return render_template('dashboard.html', voters=voters, candidates=candidates, votes=votes)


@admin.route('/arm', methods=['GET', 'POST'])
@login_required
@admin_required
def arm():
    if request.method == 'POST':
        passphrase = uuid.uuid4().hex
        pubkey_pem, encrypted_privkey = generate_rsa_keys(passphrase)

        with open('app/var/pubkey.pem', 'wb') as f:
            f.write(pubkey_pem)

        with open('app/var/privkey.pem', 'wb') as f:
            f.write(encrypted_privkey)

        admin_logger.info('Arm successful')
        return render_template('arm.html', passphrase=passphrase)
    else:
        return render_template('arm.html')


@admin.route('/download-key', methods=['POST'])
@login_required
@admin_required
def download_key():
    @after_this_request
    def remove_privkey(response):
        os.remove('app/var/privkey.pem')
        return response

    admin_logger.info('Downloaded private key')
    return send_file('var/privkey.pem', as_attachment=True)


@admin.route('/deliberate', methods=['GET', 'POST'])
@login_required
@admin_required
def deliberate():
    if request.method == 'POST':
        db = DatabaseHandler('app/var/db.sqlite')

        votes = db.get_votes()
        candidates = db.get_candidates()
        passphrase = request.form['password']

        results = {}
        for candidate in candidates:
            results[candidate[0]] = {'name': candidate[1], 'votes': 0}

        file = request.files['file']
        if file.filename == '':
            flash('No selected file', 'danger')
            return render_template('no_stress.html')

        if file:
            try:
                filename = uuid.uuid4()
                filepath = os.path.join(current_app.config['FILE_UPLOADS'], f'{filename}.pem')
                file.save(filepath)
                with open(filepath, 'r') as f:
                    privkey = f.read().encode()
            except Exception:
                flash('Invalid file', 'danger')
                return render_template('no_stress.html')

            for vote in votes:
                encrypted_ballot = hex(int.from_bytes(vote[0], 'big'))[2:]
                ballot = decrypt_ballot(bytes.fromhex(encrypted_ballot), privkey, passphrase)
                ballot = ballot.split('/')
                results[int(ballot[0])]['votes'] += 1

            os.remove(filepath)
            db.close_connection()

            admin_logger.info('Deliberation successful')
            flash('Deliberation successful', 'success')
            return render_template('no_stress.html')
    else:
        return render_template('no_stress.html')
