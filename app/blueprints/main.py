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

    buildings_info = db.get_buildings_with_icon()   # {name: {'icon': ..., 'color': ...}}
    building_stats = {}
    any_data = False

    if buildings_info:
        for building_name, info in buildings_info.items():
            voters_by_prom = db.count_voters_by_promotion_and_building(building_name)
            votes_by_prom  = db.count_votes_by_promotion_and_building(building_name)
            percentages = {}
            if voters_by_prom:
                any_data = True
                for prom, voters in voters_by_prom:
                    votes = next((v for p, v in votes_by_prom if p == prom), 0)
                    percentages[prom] = round(votes / voters * 100, 2)
            building_stats[building_name] = {
                'label':       building_name,
                'percentages': percentages,
                'icon':        info['icon'],
                'color':       info['color'],
            }

        # Also catch voters not assigned to any configured building
        # (imported before the feature, or registered with an empty/unknown building).
        known = list(buildings_info.keys())
        unassigned_voters = db.count_voters_by_promotion_unassigned(known)
        unassigned_votes  = db.count_votes_by_promotion_unassigned(known)
        if unassigned_voters:
            any_data = True
            percentages = {}
            for prom, voters in unassigned_voters:
                votes = next((v for p, v in unassigned_votes if p == prom), 0)
                percentages[prom] = round(votes / voters * 100, 2)
            building_stats['__unassigned__'] = {
                'label':       'Non assigné',
                'percentages': percentages,
                'icon':        'question-circle',
                'color':       '#6b7280',
            }
    else:
        # No buildings configured — show aggregate stats for all voters.
        voters_by_prom = db.count_voters_by_promotion()
        votes_by_prom  = db.count_votes_by_promotion()
        if voters_by_prom:
            any_data = True
            percentages = {}
            for prom, voters in voters_by_prom:
                votes = next((v for p, v in votes_by_prom if p == prom), 0)
                percentages[prom] = round(votes / voters * 100, 2)
            building_stats['__all__'] = {
                'label':       'Tous les électeurs',
                'percentages': percentages,
                'icon':        'people',
                'color':       '#2563eb',
            }

    db.close_connection()

    if any_data:
        return render_template('index.html', building_stats=building_stats)
    else:
        flash('Aucune donnée disponible.', 'warning')
        return render_template('index.html')
