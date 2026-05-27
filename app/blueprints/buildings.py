from flask import Blueprint, render_template, request, flash, redirect, url_for
from flask_login import login_required

from .auth import admin_required, technician_required
from .database import DatabaseHandler

import logging


buildings_bp = Blueprint('buildings', __name__)

buildings_logger = logging.getLogger(__name__)
buildings_logger.setLevel(logging.INFO)
_handler = logging.FileHandler('./app/logs/admin.log')
_handler.setLevel(logging.INFO)
_handler.setFormatter(logging.Formatter('%(asctime)s - %(message)s'))
buildings_logger.addHandler(_handler)


@buildings_bp.route('/buildings', methods=['GET', 'POST'])
@login_required
@technician_required
def buildings():
    if request.method == 'POST':
        name  = request.form.get('name', '').strip()
        icon  = request.form.get('icon', 'building').strip() or 'building'
        color = request.form.get('color', '#2563eb').strip() or '#2563eb'
        if not name:
            flash('Le nom du bâtiment ne peut pas être vide.', 'danger')
            return redirect(url_for('buildings.buildings'))
        try:
            with DatabaseHandler('app/var/db.sqlite') as db:
                db.add_building(name, icon, color)
            buildings_logger.info(f"Building added: {name} (icon={icon}, color={color})")
            flash(f'Bâtiment « {name} » ajouté avec succès.', 'success')
        except Exception as e:
            if 'UNIQUE' in str(e):
                flash(f'Un bâtiment nommé « {name} » existe déjà.', 'danger')
            else:
                flash(f'Erreur lors de l\'ajout : {e}', 'danger')
        return redirect(url_for('buildings.buildings'))

    with DatabaseHandler('app/var/db.sqlite') as db:
        all_buildings = db.get_buildings()
    return render_template('buildings.html', buildings=all_buildings)


@buildings_bp.route('/buildings/<int:building_id>/delete', methods=['POST'])
@login_required
@admin_required
def delete_building(building_id):
    with DatabaseHandler('app/var/db.sqlite') as db:
        db.delete_building(building_id)
    buildings_logger.info(f"Building deleted: id={building_id}")
    flash('Bâtiment supprimé.', 'success')
    return redirect(url_for('buildings.buildings'))
