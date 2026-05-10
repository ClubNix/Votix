from flask import Blueprint, render_template, flash
from dotenv import dotenv_values

from .votix import DatabaseHandler

import time

main = Blueprint('main', __name__)

DOTENV_PATH = './app/.env'


@main.route('/')
def index():
    current_time = int(time.time())
    cfg = dotenv_values(DOTENV_PATH)
    voting_start = int(cfg.get('VOTING_START', 0) or 0)
    voting_end   = int(cfg.get('VOTING_END', 0) or 0)

    if current_time < voting_start:
        flash("Le vote n'a pas encore commencé.", 'warning')
        return render_template('index.html')
    if current_time > voting_end:
        flash('Le vote est terminé.', 'warning')

    db = DatabaseHandler('app/var/db.sqlite')
    voters_by_prom = db.count_voters_by_promotion()
    votes_by_prom  = db.count_votes_by_promotion()
    db.close_connection()

    percentages = {}
    if votes_by_prom and voters_by_prom:
        for prom, voters in voters_by_prom:
            votes = next((v for p, v in votes_by_prom if p == prom), 0)
            percentages[prom] = round(votes / voters * 100, 2)
        return render_template('index.html', percentages=percentages)
    else:
        flash('Aucune donnée disponible.', 'warning')
        return render_template('index.html')
