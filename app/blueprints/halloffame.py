import os
import time
import logging

import requests
import yaml
from flask import Blueprint, render_template, redirect, url_for, flash

halloffame_bp = Blueprint('halloffame', __name__)

_DEFAULT_URL = 'https://raw.githubusercontent.com/ClubNix/votix-data-esiee/main/data/halloffame.yml'

_cache: dict = {'data': None, 'ts': 0, 'url': None}

logger = logging.getLogger(__name__)


def _fetch() -> tuple[list, str | None]:
    url = os.getenv('HALLOFFAME_URL', _DEFAULT_URL)
    ttl = int(os.getenv('HALLOFFAME_CACHE_TTL', '3600'))
    now = time.time()

    # Invalidate cache if the URL was changed via configure
    if url != _cache['url']:
        _cache['ts'] = 0
        _cache['url'] = url

    if _cache['data'] is not None and now - _cache['ts'] < ttl:
        return _cache['data'], None

    try:
        resp = requests.get(url, timeout=8)
        resp.raise_for_status()
        raw = yaml.safe_load(resp.text)
        archives = raw.get('archives', {})
        years = []
        for _, entry in archives.items():
            total = entry.get('voters', 0)
            winner_name = entry.get('winner', '')
            winner_color = '#2563eb'
            candidates = []
            for c in entry.get('candidates', []):
                color = c.get('color', '#6b7280')
                if c.get('name') == winner_name:
                    winner_color = color
                candidates.append({
                    'name':       c.get('name', ''),
                    'votes':      c.get('votes', 0),
                    'color':      color,
                    'electable':  c.get('electable', True),
                    'percentage': round(c.get('votes', 0) / total * 100, 1) if total else 0,
                })
            candidates.sort(key=lambda c: c['votes'], reverse=True)
            years.append({
                'year':         entry.get('year'),
                'winner':       winner_name,
                'winner_color': winner_color,
                'voters':       total,
                'candidates':   candidates,
            })
        years.sort(key=lambda y: y['year'], reverse=True)
        _cache['data'] = years
        _cache['ts'] = now
        return years, None
    except Exception as e:
        logger.error(f"Failed to fetch hall of fame: {e}")
        return _cache['data'] or [], str(e)


def is_enabled() -> bool:
    from dotenv import dotenv_values as _dv
    cfg = _dv('./app/.env')
    return cfg.get('HALLOFFAME_ENABLED', 'True').lower() == 'true'


@halloffame_bp.route('/palmares')
def palmares():
    if not is_enabled():
        flash('La page Palmarès est désactivée.', 'warning')
        return redirect(url_for('main.index'))
    years, error = _fetch()
    return render_template('halloffame.html', years=years, error=error)
