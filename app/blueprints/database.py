import sqlite3
from ..models import Voter, Candidate
import logging


database_logger = logging.getLogger(__name__)
database_logger.setLevel(logging.INFO)
handler = logging.FileHandler('./app/logs/database.log')
handler.setLevel(logging.INFO)
formatter = logging.Formatter('%(asctime)s - %(message)s')
handler.setFormatter(formatter)
database_logger.addHandler(handler)


class DatabaseHandler:
    def __init__(self, db_name: str):
        self.conn = sqlite3.connect(db_name)
        self.cursor = self.conn.cursor()

    def __enter__(self):
        return self

    def __exit__(self, exc_type, exc_val, exc_tb):
        if exc_type is not None:
            self.conn.rollback()
        self.conn.close()
        return False

    def close_connection(self):
        self.conn.close()

    def get_voters(self):
        self.cursor.execute('SELECT * FROM voters')
        return self.cursor.fetchall()

    def count_voters(self):
        self.cursor.execute('SELECT COUNT(*) FROM voters')
        return self.cursor.fetchone()

    def count_voters_by_promotion(self):
        self.cursor.execute('SELECT promotion, COUNT(*) FROM voters GROUP BY promotion')
        return self.cursor.fetchall()

    def get_voter_by_email(self, email: str):
        self.cursor.execute('SELECT * FROM voters WHERE email = ?', (email,))
        return self.cursor.fetchone()

    @staticmethod
    def get_voter_by_link(link_string: str):
        return Voter.query.filter_by(link_string=link_string).first()

    def add_voter(self, voter: Voter):
        self.cursor.execute('INSERT INTO voters (last_name, first_name, email, promotion, building, voted, link_string, '
                            'secret, invitation_sent, link_sent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                            (voter.last_name, voter.first_name, voter.email, voter.promotion,
                             voter.building or '',
                             1 if voter.voted else 0,
                             voter.link_string, voter.secret,
                             1 if voter.invitation_sent else 0,
                             1 if voter.link_sent else 0))
        database_logger.info(f"Added voter {voter.email}")
        self.conn.commit()

    def get_candidates(self):
        self.cursor.execute('SELECT * FROM candidates')
        return self.cursor.fetchall()

    def get_eligible_candidates(self):
        self.cursor.execute('SELECT * FROM candidates WHERE eligible = 1')
        return self.cursor.fetchall()

    def get_candidate(self, candidate_id: int):
        self.cursor.execute('SELECT * FROM candidates WHERE id = ?', (candidate_id,))
        return self.cursor.fetchone()

    def get_eligible_candidate(self, candidate_id: int):
        self.cursor.execute('SELECT * FROM candidates WHERE id = ? AND eligible = 1', (candidate_id,))
        return self.cursor.fetchone()

    def add_candidate(self, candidate: Candidate):
        self.cursor.execute('INSERT INTO candidates (name, eligible, logo) VALUES (?, ?, ?)',
                            (candidate.name, candidate.eligible, candidate.logo or ''))
        self.conn.commit()
        database_logger.info(f"Added candidate {candidate.name}")

    def update_candidate(self, candidate_id: int, name: str, eligible: bool, logo: str):
        self.cursor.execute('UPDATE candidates SET name = ?, eligible = ?, logo = ? WHERE id = ?',
                            (name, eligible, logo, candidate_id))
        self.conn.commit()
        database_logger.info(f"Updated candidate {candidate_id}")

    def delete_candidate(self, candidate_id: int):
        self.cursor.execute('DELETE FROM candidates WHERE id = ?', (candidate_id,))
        self.conn.commit()
        database_logger.info(f"Deleted candidate {candidate_id}")

    def get_votes(self):
        self.cursor.execute('SELECT ballot FROM voters WHERE voted = 1')
        return self.cursor.fetchall()

    def count_votes(self):
        self.cursor.execute('SELECT COUNT(*) FROM voters WHERE voted = 1')
        return self.cursor.fetchone()

    def count_votes_by_promotion(self):
        self.cursor.execute('SELECT promotion, COUNT(*) FROM voters WHERE voted = 1 GROUP BY promotion')
        return self.cursor.fetchall()

    def count_voters_by_promotion_and_building(self, building: str):
        self.cursor.execute('SELECT promotion, COUNT(*) FROM voters WHERE building = ? GROUP BY promotion', (building,))
        return self.cursor.fetchall()

    def count_votes_by_promotion_and_building(self, building: str):
        self.cursor.execute('SELECT promotion, COUNT(*) FROM voters WHERE voted = 1 AND building = ? GROUP BY promotion', (building,))
        return self.cursor.fetchall()

    def count_voters_by_promotion_unassigned(self, known_buildings: list):
        """Voters whose building is empty or not in known_buildings."""
        if not known_buildings:
            return []
        placeholders = ','.join('?' * len(known_buildings))
        self.cursor.execute(
            f"SELECT promotion, COUNT(*) FROM voters "
            f"WHERE building = '' OR building IS NULL OR building NOT IN ({placeholders}) "
            f"GROUP BY promotion",
            known_buildings,
        )
        return self.cursor.fetchall()

    def count_votes_by_promotion_unassigned(self, known_buildings: list):
        """Votes from voters whose building is empty or not in known_buildings."""
        if not known_buildings:
            return []
        placeholders = ','.join('?' * len(known_buildings))
        self.cursor.execute(
            f"SELECT promotion, COUNT(*) FROM voters "
            f"WHERE voted = 1 AND (building = '' OR building IS NULL OR building NOT IN ({placeholders})) "
            f"GROUP BY promotion",
            known_buildings,
        )
        return self.cursor.fetchall()

    def delete_voter(self, voter_id: int):
        self.cursor.execute('DELETE FROM voters WHERE id = ?', (voter_id,))
        self.conn.commit()
        database_logger.info(f"Deleted voter {voter_id}")

    # ── Buildings ──────────────────────────────────────────────────────────

    def get_buildings(self):
        """Return list of (id, name, icon, color) tuples ordered by name."""
        self.cursor.execute('SELECT id, name, icon, color FROM buildings ORDER BY name')
        return self.cursor.fetchall()

    def get_building_names(self) -> list[str]:
        self.cursor.execute('SELECT name FROM buildings ORDER BY name')
        return [row[0] for row in self.cursor.fetchall()]

    def get_buildings_with_icon(self) -> dict[str, dict]:
        """Return {name: {'icon': ..., 'color': ...}} mapping for use in templates."""
        self.cursor.execute('SELECT name, icon, color FROM buildings ORDER BY name')
        return {row[0]: {'icon': row[1], 'color': row[2] or '#2563eb'} for row in self.cursor.fetchall()}

    def add_building(self, name: str, icon: str = 'building', color: str = '#2563eb'):
        self.cursor.execute('INSERT INTO buildings (name, icon, color) VALUES (?, ?, ?)', (name, icon, color))
        self.conn.commit()
        database_logger.info(f"Added building {name}")

    def delete_building(self, building_id: int):
        self.cursor.execute('DELETE FROM buildings WHERE id = ?', (building_id,))
        self.conn.commit()
        database_logger.info(f"Deleted building {building_id}")

    def add_vote(self, voter: Voter, ballot: bytes):
        self.cursor.execute('UPDATE voters SET ballot = ? WHERE id = ?', (ballot, voter.id,))
        self.cursor.execute('UPDATE voters SET voted = 1 WHERE id = ?', (voter.id,))
        self.conn.commit()
