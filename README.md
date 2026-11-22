# Plugin Jeedom - Devialet

Plugin Jeedom pour piloter les enceintes Devialet (Phantom I/II, Reactor, Expert Pro, Dialog, etc.) via l'API locale **IP Control**.

## Fonctionnalités

- Découverte/contrôle par IP locale (pas de cloud)
- Volume : lecture, réglage, +/-
- Lecture : play, pause, suivant, précédent
- Muet / son
- Sélection de source
- Mode nuit (night mode / nightModeOverlay selon firmware)
- Mise hors tension (veille)
- Rafraîchissement périodique configurable (cron)

## Prérequis

- Jeedom 4.4+
- Enceinte Devialet compatible IP Control sur le même réseau local
- **IP Control activé** dans l'application Devialet : `Paramètres > IP Control`
- IP fixe ou réservation DHCP recommandée

## Installation

1. Copier le dossier `plugin-devialet` dans `/var/www/html/plugins/devialet` (ou installer depuis votre source).
2. Activer le plugin dans Jeedom.
3. Créer un équipement, renseigner l'IP de l'enceinte (ou du leader en stéréo).
4. Sauvegarder : les commandes sont créées automatiquement.
5. Tester la connexion avec le bouton **Tester**.

## MCP / JeedomMCP

Si le plugin **JeedomMCP** est installé et actif sur votre Jeedom, les équipements et commandes Devialet créés par ce plugin sont automatiquement exposés via MCP (selon la configuration ACL/whitelist de JeedomMCP).

## API Devialet utilisée

Le plugin utilise l'API locale Devialet IP Control :

- Base : `http://<ip>/ipcontrol/v1/{systems|devices|groups}/current/`
- Volume : `GET/POST .../sources/current/soundControl/volume`
- Lecture : `POST .../sources/current/playback/{play|pause|next|previous|mute|unmute}`
- Sources : `GET .../sources`, sélection `POST .../sources/{sourceId}/playback/play`
- Mode nuit : `GET/POST .../settings/audio/nightMode` ou `nightModeOverlay`

## Limitations connues

- La sortie de veille (wake) n'est généralement pas possible via IP Control ; utiliser le bouton physique ou l'app Devialet.
- L'API IP Control est mono-thread : éviter les rafraîchissements trop agressifs.
- Les réponses exactes peuvent varier selon les modèles/firmwares DOS 2.x / 3.x ; le plugin lit les champs de façon défensive.

## Licence

AGPL
