from flask import Flask
from flask_sqlalchemy import SQLAlchemy
from flask_login import LoginManager
from flask_wtf.csrf import CSRFProtect
from werkzeug.middleware.proxy_fix import ProxyFix
from dotenv import load_dotenv

import os


db = SQLAlchemy()
csrf = CSRFProtect()

load_dotenv(dotenv_path='./app/.env', override=True)


_PLACEHOLDER_SECRETS = {'changeme', 'a-long-random-string', 'secret', 'dev'}
_MIN_SECRET_LENGTH = 32


def _require_secret_key(value):
    """Refuse to start with a missing, placeholder or short SECRET_KEY.

    The key signs the session cookie that carries the logged-in user id, so a
    guessable value lets anyone forge an admin session.
    """
    if not value or value.strip().lower() in _PLACEHOLDER_SECRETS or len(value) < _MIN_SECRET_LENGTH:
        raise RuntimeError(
            'SECRET_KEY is missing, a placeholder, or shorter than '
            f'{_MIN_SECRET_LENGTH} characters. Generate one with: '
            'python3 -c "import secrets; print(secrets.token_hex(32))"'
        )
    return value


def create_app():
    app = Flask(__name__)
    app.wsgi_app = ProxyFix(app.wsgi_app, x_proto=1, x_host=1)

    app.config['SECRET_KEY']    = _require_secret_key(os.getenv('SECRET_KEY'))
    app.config['SQLALCHEMY_DATABASE_URI'] = f'sqlite:///{os.path.join(os.path.dirname(__file__), "var", "db.sqlite")}'
    app.config['FILE_UPLOADS']  = os.path.join(os.path.dirname(__file__), 'static', 'uploads')

    # Cross-site request protection: every state-changing request must carry
    # a CSRF token, and session cookies are not sent on cross-site POSTs.
    app.config['WTF_CSRF_TIME_LIMIT']      = None  # token valid for the whole session
    app.config['SESSION_COOKIE_SAMESITE']  = 'Lax'
    app.config['REMEMBER_COOKIE_SAMESITE'] = 'Lax'
    app.config['SESSION_COOKIE_HTTPONLY']  = True
    app.config['REMEMBER_COOKIE_HTTPONLY'] = True
    _secure_cookies = os.getenv('SESSION_COOKIE_SECURE', 'False').lower() == 'true'
    app.config['SESSION_COOKIE_SECURE']    = _secure_cookies
    app.config['REMEMBER_COOKIE_SECURE']   = _secure_cookies

    # Runtime-mutable config (editable via /configure without restart)
    app.config['VOTING_URL']    = os.getenv('VOTING_URL', '')
    app.config['VOTING_START']  = int(os.getenv('VOTING_START', 0))
    app.config['VOTING_END']    = int(os.getenv('VOTING_END', 0))
    app.config['ADMIN_EMAIL']   = os.getenv('ADMIN_EMAIL', '')
    app.config['VALID_EMAIL_DOMAINS'] = [d.strip() for d in os.getenv('VALID_EMAIL_DOMAINS', '').split(',') if d.strip()]
    app.config['SMTP_SERVER']   = os.getenv('SMTP_SERVER', '')
    app.config['SMTP_PORT']     = int(os.getenv('SMTP_PORT', 465))
    app.config['SMTP_USERNAME'] = os.getenv('SMTP_USERNAME', '')
    app.config['SMTP_PASSWORD'] = os.getenv('SMTP_PASSWORD', '')
    app.config['SMTP_FROM']     = os.getenv('SMTP_FROM', '')
    app.config['SMTP_REPLY_TO'] = os.getenv('SMTP_REPLY_TO', '')
    app.config['SMTP_VERIFY_SSL'] = os.getenv('SMTP_VERIFY_SSL', 'True')

    db.init_app(app)
    csrf.init_app(app)

    from .models import User, Building

    with app.app_context():
        try:
            db.create_all()

            # Migrate: add logo column to candidates if missing
            import sqlite3 as _sqlite3
            _db_path = os.path.join(os.path.dirname(__file__), 'var', 'db.sqlite')
            with _sqlite3.connect(_db_path) as _conn:
                _cols = [r[1] for r in _conn.execute('PRAGMA table_info(candidates)').fetchall()]
                if 'logo' not in _cols:
                    _conn.execute("ALTER TABLE candidates ADD COLUMN logo TEXT DEFAULT ''")
                    _conn.commit()

                # Migrate: add building column to voters if missing
                _cols = [r[1] for r in _conn.execute('PRAGMA table_info(voters)').fetchall()]
                if 'building' not in _cols:
                    _conn.execute("ALTER TABLE voters ADD COLUMN building TEXT DEFAULT ''")
                    _conn.commit()

                # Migrate: add building column to users if missing
                _cols = [r[1] for r in _conn.execute('PRAGMA table_info(users)').fetchall()]
                if 'building' not in _cols:
                    _conn.execute("ALTER TABLE users ADD COLUMN building TEXT DEFAULT ''")
                    _conn.commit()

                # Migrate: add icon / color columns to buildings if missing
                _cols = [r[1] for r in _conn.execute('PRAGMA table_info(buildings)').fetchall()]
                if 'icon' not in _cols:
                    _conn.execute("ALTER TABLE buildings ADD COLUMN icon TEXT DEFAULT 'building'")
                    _conn.commit()
                if 'color' not in _cols:
                    _conn.execute("ALTER TABLE buildings ADD COLUMN color TEXT DEFAULT '#2563eb'")
                    _conn.commit()

            from app.blueprints import auth

            if User.query.filter_by(role='admin').first() is None:
                print('No admin user found, don\'t forget to create one!\n')

        except Exception as e:
            print(e)

    login_manager = LoginManager()
    login_manager.login_view = 'auth.login'
    login_manager.init_app(app)

    @login_manager.user_loader
    def load_user(user_id):
        return User.query.get(int(user_id))

    from .blueprints.main import main
    from .blueprints.auth import auth
    from .blueprints.admin import admin
    from .blueprints.votix import votix
    from .blueprints.configure import configure_bp
    from .blueprints.google_workspace import google_ws
    from .blueprints.buildings import buildings_bp
    from .blueprints.halloffame import halloffame_bp

    app.register_blueprint(main)
    app.register_blueprint(auth)
    app.register_blueprint(admin)
    app.register_blueprint(votix)
    app.register_blueprint(configure_bp)
    app.register_blueprint(google_ws)
    app.register_blueprint(buildings_bp)
    app.register_blueprint(halloffame_bp)

    @app.context_processor
    def inject_globals():
        from dotenv import dotenv_values as _dv
        from .blueprints.google_workspace import is_connected as _gw_connected
        pubkey_path = os.path.join(os.path.dirname(__file__), 'var', 'pubkey.pem')
        cfg = _dv('./app/.env')
        return {
            'is_armed':           os.path.isfile(pubkey_path),
            'association_name':   cfg.get('ASSOCIATION_NAME', ''),
            'google_connected':   _gw_connected(),
            'halloffame_enabled': cfg.get('HALLOFFAME_ENABLED', 'True').lower() == 'true',
        }

    return app
