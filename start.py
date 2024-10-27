from app import create_app

import logging


logging.basicConfig(
    filename='./app/logs/app.log',
    level=logging.INFO,
    format='%(asctime)s - %(message)s')

app = create_app()

if __name__ == '__main__':
    app.run(debug=True)
