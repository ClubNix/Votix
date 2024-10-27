from . import db
from flask_login import UserMixin


class User(UserMixin, db.Model):
    __tablename__ = 'users'
    id = db.Column(db.Integer, primary_key=True, autoincrement=True)
    email = db.Column(db.String(100), unique=True)
    password = db.Column(db.String(100))
    username = db.Column(db.String(100))
    role = db.Column(db.String(100), default='user')


class Candidate(db.Model):
    __tablename__ = 'candidates'
    id = db.Column(db.Integer, primary_key=True, autoincrement=True)
    name = db.Column(db.String(100))
    eligible = db.Column(db.Boolean, default=False)


class Voter(db.Model):
    __tablename__ = 'voters'
    id = db.Column(db.Integer, primary_key=True, autoincrement=True)
    last_name = db.Column(db.String(100))
    first_name = db.Column(db.String(100))
    email = db.Column(db.String(100), unique=True)
    promotion = db.Column(db.String(100))
    link_string = db.Column(db.String(100), unique=True)
    secret = db.Column(db.String(4))
    voted = db.Column(db.Boolean, default=0)
    invitation_sent = db.Column(db.Boolean, default=0)
    link_sent = db.Column(db.Boolean, default=0)
    ballot = db.Column(db.String(200), default='')
