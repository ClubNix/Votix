from flask import Flask
from flask_sqlalchemy import SQLAlchemy
from flask_login import LoginManager
from dotenv import load_dotenv

import os


db = SQLAlchemy()

load_dotenv(dotenv_path='./app/.env', override=True)


def create_app():
    app = Flask(__name__)

    app.config['SECRET_KEY']    = os.getenv('SECRET_KEY')
    app.config['SQLALCHEMY_DATABASE_URI'] = f'sqlite:///{os.path.join(os.path.dirname(__file__), "var", "db.sqlite")}'
    app.config['FILE_UPLOADS']  = os.path.join(os.path.dirname(__file__), 'static', 'uploads')

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

    from .models import User

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

    app.register_blueprint(main)
    app.register_blueprint(auth)
    app.register_blueprint(admin)
    app.register_blueprint(votix)
    app.register_blueprint(configure_bp)

    return app
