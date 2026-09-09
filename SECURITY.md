# Security

Do not commit environment files, credentials, API tokens, private keys, JWT passphrases, database passwords, or deployment secrets.

Use `.env.example` only as a template. Store real values in `.env`, `.env.local`, secret managers, CI/CD secrets, or deployment environment variables outside version control.

If a secret is committed accidentally, rotate it immediately and rewrite Git history before continuing development.
