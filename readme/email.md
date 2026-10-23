# E-mails sortants

Tous les `wp_mail()` du site — formulaire de contact, création de compte, mot
de passe perdu — partent par le transport que configure le mu-plugin
**`rapkalin-mail`** (`website/app/mu-plugins/rapkalin-mail/`). Le thème n'en
configure aucun.

## Réglages → Email

Page réservée aux administrateurs (`manage_options`). Le rôle « Contributeur
LCDS » n'y a pas accès (403).

| Réglage | Effet |
| --- | --- |
| **Sendmail** | Le binaire `sendmail` du serveur (`sendmail_path` de PHP). Valeur par défaut tant que rien n'est enregistré. |
| **SMTP** | Le relais saisi : hôte, port, chiffrement (aucun, STARTTLS, SSL/TLS), identifiant, mot de passe. Identifiant vide = relais sans authentification. |
| **Expéditeur** | Adresse et nom. Vides : `MAIL_FROM` / `MAIL_FROM_NAME` du `.env`. |
| **Envoyer un e-mail de test** | Envoie avec les réglages **enregistrés** et affiche l'erreur exacte de PHPMailer en cas d'échec. |

## Le `.env` prime quand `SMTP_HOST` est renseigné

Si le `.env` définit `SMTP_HOST`, **toute** la configuration vient du `.env`
(`SMTP_*`, `MAIL_FROM*`) et la page l'annonce par un bandeau. C'est ce qui garde
le poste de dev sur **Mailpit** même après l'import d'une base de production :
sans cette règle, les réglages de prod importés enverraient de vrais e-mails
depuis une machine de développement.

Sur un serveur, **laisser `SMTP_HOST` vide** pour que la page décide. Un
serveur dont le `.env` le renseigne garde le comportement d'avant la 3.1.0.

## Le mot de passe SMTP

- **Chiffré en base** (libsodium, `secretbox`). La clé est dérivée de
  `AUTH_KEY` + `AUTH_SALT` du `.env` : un dump de base seul ne le révèle pas.
- **En écriture seule** : jamais réaffiché. Champ vide à l'enregistrement = on
  garde celui qui est stocké. Identifiant vidé = mot de passe supprimé.
- **Changer les sels WordPress le rend illisible.** La page le signale ; il
  suffit de le ressaisir. Aucun envoi ne part avec un mot de passe faux.

## Sendmail en local

L'image Docker n'a pas de binaire `sendmail` : choisir Sendmail en local fait
échouer l'envoi (« Exécution impossible : /usr/sbin/sendmail »). Sans effet
tant que le `.env` local renseigne `SMTP_HOST`, ce qui est le cas par défaut.
