
## Terminologie Votix

```
Terminologie française
Votant = une personne ayant voté
Invité = une personne invitée à voter
Candidat = un choix affiché, peut être une liste, éligible ou non, ou le choix blanc
Liste = une liste candidate, affichée au moment du vote
Liste éligible = une liste pouvant être votée
Liste non éligible = une liste affichée mais ne pouvant pas être votée (option grisée)
Choix blanc = considéré comme un candidat éligible par le système
Invitation = email avec un lien pour voter
Lien = un lien pour exprimer
Rappel = email avec avec lien pendant l'ouverture du vote
Code de sécurité = code à plusieurs chiffres demandé pour valider le vote, envoyé en même temps que le mail
Bulletin de vote = numéro de candidat chiffré associé à un invité, un invité ne peut avoir qu'un seul bulletin de vote
Clef privée = Clef utilisée par Votix pour déchiffrer le résultat, seul le BDE dispose de la clef privée, et la récupère durant la procédure d'armement
Clef publique = Clef utilisée par Votix pour chiffrer un bulletin de vote
Résultats = Informations affichés à l'écran après déchiffrement des votes : Nom, nombre de voix, pourcentage sur les votants
Pourcentage de participation = nb votants / nb invités
Heures / plages d'ouverture = Période où il est possible de voter.
Annonce des résultats = Annonce officielle par le représentant du BDE des résultats déchiffrés
(procédure d') Armement = Procédure durant laquelle le représentant du BDE génère un couple clef privée / publique
(procédure de) Déchiffrement = Procédure durant laquelle le représentant du BDE déchiffre les votes avec la clef privée et le code secret de déchiffrement donné par le responsable de l'intégrité
(procédure de) Vérification = Petit temps où il est encore possible à l'équipe technique de déclarer le vote nul pour raison technique
(procédure de) Validation = Vérification après annonce des résultats pour contrôler que les résultats annoncés par le représentant du BDE était bien ceux affichés

Terminologie anglaise
Token = jeton dans le lien pour indentifier de quel mail / invité il s'agit
Voter = une personne pouvant voter ou ayant voté
Candidate = comme Candidat en français
Ballot = bulletin de vote, contient le numéro du candidat chiffré, ne pouvant 
Signature = empreinte numérique pour qu'un invité puisse reconsulter pour qui il a voté (pas encore implémenté)
Vote counting = comptage des votes, correspond au déchiffrement
```