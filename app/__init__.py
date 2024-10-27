from flask import Flask
from flask_sqlalchemy import SQLAlchemy
from flask_login import LoginManager
from dotenv import load_dotenv

import os


db = SQLAlchemy()

load_dotenv(dotenv_path='./app/.env')
_SECRET_KEY = os.getenv("SECRET_KEY")
_VOTING_URL = os.getenv("VOTING_URL")


def create_app():
    app = Flask(__name__)

    app.config['SECRET_KEY'] = _SECRET_KEY
    app.config['SQLALCHEMY_DATABASE_URI'] = f'sqlite:///{os.path.join(os.path.dirname(__file__), "var", "db.sqlite")}'
    app.config['FILE_UPLOADS'] = os.path.join(os.path.dirname(__file__), 'static', 'uploads')
    app.config['VOTING_URL'] = _VOTING_URL

    db.init_app(app)

    from .models import User

    with app.app_context():
        try:
            db.create_all()

            from app.blueprints import auth

            if User.query.filter_by(role='admin').first() is None:
                print('No admin user found, creating one...\n')
                auth.create_user(role='admin')
                print('\nAdmin user created successfully')

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

    app.register_blueprint(main)
    app.register_blueprint(auth)
    app.register_blueprint(admin)
    app.register_blueprint(votix)

    return app
