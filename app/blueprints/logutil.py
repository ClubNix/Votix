import logging


LOG_FORMAT = '%(asctime)s - %(message)s'


def escape_log_value(value) -> str:
    """Neutralise CR/LF so user input cannot start a new (forged) log line."""
    return str(value).replace('\r', '\\r').replace('\n', '\\n')


class SafeFormatter(logging.Formatter):
    """Formatter that escapes line breaks in the log message.

    Exception tracebacks appended by logging are left untouched.
    """

    def __init__(self, fmt=LOG_FORMAT, *args, **kwargs):
        super().__init__(fmt, *args, **kwargs)

    def formatMessage(self, record):
        record.message = escape_log_value(record.message)
        return super().formatMessage(record)
