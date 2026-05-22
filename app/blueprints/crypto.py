from cryptography.hazmat.primitives import serialization
from cryptography.hazmat.primitives.asymmetric import rsa
from cryptography.hazmat.backends import default_backend
from cryptography.hazmat.primitives import hashes
from cryptography.hazmat.primitives.asymmetric import padding


def generate_rsa_keys(passphrase: str):
    # Generate a new RSA key pair
    private_key = rsa.generate_private_key(
        public_exponent=65537,
        key_size=2048,
        backend=default_backend()
    )
    public_key = private_key.public_key()

    # Encrypt the private key with the passphrase
    encrypted_privkey = private_key.private_bytes(
        encoding=serialization.Encoding.PEM,
        format=serialization.PrivateFormat.PKCS8,
        encryption_algorithm=serialization.BestAvailableEncryption(passphrase.encode())
    )

    # Save the public key in PEM format
    pubkey_pem = public_key.public_bytes(
        encoding=serialization.Encoding.PEM,
        format=serialization.PublicFormat.SubjectPublicKeyInfo
    )

    return pubkey_pem, encrypted_privkey


def encrypt_ballot(ballot: str, pubkey_pem: bytes, voter_uuid: str):
    ballot = f"{ballot}/{voter_uuid}"
    public_key = serialization.load_pem_public_key(pubkey_pem, backend=default_backend())
    ballot_bytes = ballot.encode('utf-8')
    encrypted_ballot = public_key.encrypt(
        ballot_bytes,
        padding.OAEP(
            mgf=padding.MGF1(algorithm=hashes.SHA256()),
            algorithm=hashes.SHA256(),
            label=None
        )
    )
    return encrypted_ballot


def load_private_key(encrypted_privkey: bytes, passphrase: str):
    return serialization.load_pem_private_key(
        encrypted_privkey,
        password=passphrase.encode(),
        backend=default_backend()
    )


def decrypt_ballot(encrypted_ballot: bytes, private_key):
    ballot = private_key.decrypt(
        encrypted_ballot,
        padding.OAEP(
            mgf=padding.MGF1(algorithm=hashes.SHA256()),
            algorithm=hashes.SHA256(),
            label=None
        )
    )
    return ballot.decode('utf-8')
