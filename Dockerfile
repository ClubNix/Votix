FROM python:3
LABEL maintainer="Isnubi"

WORKDIR /app

COPY ./ /app

RUN pip install --no-cache-dir -r ./app/requirements.txt

EXPOSE 5000

CMD ["gunicorn", "-w", "4", "-b", "0.0.0.0:5000", "start:app"]
