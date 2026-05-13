from typing import ParamSpec, Callable
from flask_login import current_user
from functools import wraps
from flask import Blueprint, render_template, redirect, url_for, request, flash
from werkzeug.security import check_password_hash, generate_password_hash
from flask_login import login_user, login_required, logout_user
import click

from ..models import User
from .. import db
from .mail_sender import validate_email


auth = Blueprint('auth', __name__)


def admin_required(f: Callable[[ParamSpec("_PWrapped")], object]) -> object:
    @wraps(f)
    def decorated_function(*args, **kwargs):
        if not current_user.role == 'admin':
            flash("Vous n'avez pas la permission d'effectuer cette action.")
            return redirect(url_for('main.index'))
        return f(*args, **kwargs)
    return decorated_function


def technician_required(f: Callable[[ParamSpec("_PWrapped")], object]) -> object:
    @wraps(f)
    def decorated_function(*args, **kwargs):
        if current_user.role not in ['admin', 'technician']:
            flash("Vous n'avez pas la permission d'effectuer cette action.")
            return redirect(url_for('main.index'))
        return f(*args, **kwargs)
    return decorated_function


def create_user(role: str):
    email = click.prompt(f'Enter {role} email')
    password = click.prompt(f'Enter {role} password', hide_input=True, confirmation_prompt=True)
    username = click.prompt(f'Enter {role} username')
    new_user = User(
        email=email,
        username=username,
        password=generate_password_hash(password, method='scrypt'),
        role=role
    )
    db.session.add(new_user)
    db.session.commit()


@auth.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        email = request.form.get('email')
        password = request.form.get('password')
        remember = True if request.form.get('remember') else False

        if not validate_email(email):
            flash('Adresse email invalide.')
            render_template('login.html')

        user = User.query.filter_by(email=email).first()

        if not user or not check_password_hash(user.password, password):
            flash('Identifiants incorrects, veuillez réessayer.')
            return redirect(url_for('auth.login'))

        login_user(user, remember=remember)
        return redirect(url_for('admin.dashboard'))
    else:
        return render_template('login.html')


@auth.route('/logout')
@login_required
def logout():
    logout_user()
    return redirect(url_for('main.index'))
