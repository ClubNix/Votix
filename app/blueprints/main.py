from flask import Blueprint, render_template, flash
from dotenv import load_dotenv

from .votix import DatabaseHandler

import os
import time

main = Blueprint('main', __name__)


load_dotenv(dotenv_path='./app/.env')
_VOTING_START = int(os.getenv('VOTING_START'))
_VOTING_END = int(os.getenv('VOTING_END'))


@main.route('/')
def index():
    current_time = int(time.time())
    if current_time < _VOTING_START:
        flash('Voting has not started yet', 'warning')
        return render_template('index.html')
    if current_time > _VOTING_END:
        flash('Voting has ended', 'warning')
        return render_template('index.html')

    db = DatabaseHandler('app/var/db.sqlite')
    voters_by_prom = db.count_voters_by_promotion()
    votes_by_prom = db.count_votes_by_promotion()
    db.close_connection()

    percentages = {}

    if votes_by_prom and voters_by_prom:
        for prom, voters in voters_by_prom:
            votes = next((v for p, v in votes_by_prom if p == prom), 0)
            percentages[prom] = round(votes / voters * 100, 2)
        return render_template('index.html', percentages=percentages)
    else:
        flash('No data available', 'warning')
        return render_template('index.html')
