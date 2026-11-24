FROM python:3.13-slim
LABEL maintainer="Isnubi"
LABEL org.opencontainers.image.source="https://github.com/ClubNix/Votix"
LABEL org.opencontainers.image.description="Votix - secure online voting for student associations"

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1

WORKDIR /app

# Install dependencies first so this layer is cached across code changes.
COPY app/requirements.txt ./app/requirements.txt
RUN pip install --no-cache-dir -r ./app/requirements.txt --root-user-action=ignore

COPY . /app

# Runtime directories. The configuration lives in the mounted data volume
# (app/var/.env); app/.env points to it so the app finds it at its usual path
# and /configure persists changes across container restarts.
RUN mkdir -p app/var app/logs app/static/uploads \
    && ln -sf var/.env app/.env

EXPOSE 5000

CMD ["gunicorn", "-w", "4", "-b", "0.0.0.0:5000", "start:app"]
