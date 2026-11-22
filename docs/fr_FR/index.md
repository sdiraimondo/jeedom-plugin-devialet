# Plugin Devialet

Ce plugin permet de piloter des enceintes Devialet compatibles avec l'API locale **IP Control** depuis Jeedom.

## Prérequis

- Enceinte Devialet sur le réseau local (Phantom I/II, Reactor, Expert Pro, Dialog, Arch selon compatibilité IP Control).
- **IP Control activé** dans l'application Devialet : `Paramètres > IP Control`.
- IP fixe ou réservation DHCP recommandée.
- Pour une paire stéréo, configurer l'IP du **leader** du groupe.

## Configuration

1. Installer et activer le plugin **Devialet**.
2. Aller dans *Plugins > Multimédia > Devialet*.
3. Cliquer sur **Ajouter** et renseigner :
   - **Nom de l'équipement**
   - **Adresse IP** de l'enceinte
   - **Namespace API** : `systems` (recommandé), `devices` ou `groups`
   - **Auto-actualisation** : expression cron, par défaut `*/5 * * * *`
4. Sauvegarder l'équipement. Les commandes sont créées automatiquement.
5. Utiliser le bouton **Tester** pour vérifier la connexion.

## Commandes créées

| Commande | Type | Description |
|---|---|---|
| Rafraîchir | action | Interroge l'enceinte et met à jour les états |
| En ligne | info binaire | Disponibilité de l'API locale |
| Volume | info numérique (%) | Volume courant |
| Régler volume | action slider | Définit le volume (0-100) |
| Volume + / Volume - | action | Augmente/diminue le volume |
| Muet | info binaire | État sourdine |
| Muet / Son | action | Active/désactive la sourdine |
| Etat lecture | info string | État de lecture (`playing`, `paused`, etc.) |
| Lecture / Pause / Suivant / Précédent | action | Contrôle de lecture |
| Source | info string | Source courante |
| Sélectionner source | action select | Change la source (liste mise à jour au refresh) |
| Mode nuit | info binaire | État mode nuit |
| Mode nuit ON/OFF | action | Active/désactive le mode nuit |
| Eteindre | action | Met l'enceinte en veille |

## Exposition MCP

Si le plugin **JeedomMCP** est installé sur votre Jeedom, les équipements Devialet et leurs commandes sont accessibles via MCP. Vous pouvez alors demander à un agent IA de consulter l'état ou exécuter des actions (`refresh`, `volume_set`, `play`, etc.) selon les ACL configurées.

## Dépannage

- **Connexion échouée** : vérifier IP, réseau, et que IP Control est activé dans l'app Devialet.
- **Commandes sans effet** : certaines sources (optique/HDMI) ne supportent pas pause/play de la même façon ; utiliser mute si nécessaire.
- **Mode nuit indisponible** : dépend du firmware/modèle ; le plugin tente `nightMode` puis `nightModeOverlay`.
- **Réveil impossible** : limitation Devialet, réveil par bouton physique ou app.
