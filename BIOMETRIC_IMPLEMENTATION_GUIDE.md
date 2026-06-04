# Guide d'Implémentation de l'Authentification Biométrique (Mobile Frontend)

Ce document décrit en détail l'architecture, le parcours utilisateur et la logique conceptuelle pour intégrer l'authentification biométrique (Face ID, Touch ID, Empreinte digitale) dans votre application mobile connectée à votre API Laravel (Sanctum).

---

## 🛡️ 1. Concept de base et Sécurité Matérielle

Avant d'entamer le développement, il est crucial de comprendre la répartition des rôles en termes de sécurité :

### Le rôle du Système d'Exploitation (iOS / Android)
* **Confidentialité totale :** Les données biologiques (l'image de l'empreinte ou du visage) ne sortent **jamais** du téléphone. Ni l'application mobile, ni votre serveur backend Laravel n'y ont accès.
* **La Secure Enclave / Keystore :** Le processeur du téléphone valide l'empreinte localement. Si la validation réussit, le système d'exploitation autorise l'application mobile à accéder à un trousseau de clés chiffrées (Secure Storage).

### Le rôle du Application Mobile (Frontend)
* Elle demande au système d'exploitation de lancer la vérification biométrique.
* Elle stocke et récupère les identifiants (Email/Password ou jeton de rafraîchissement) dans le stockage sécurisé du téléphone de manière chiffrée.

### Le rôle du Serveur (Backend Laravel)
* Il reste le seul décisionnaire pour générer les jetons d'accès API (Tokens Sanctum).
* Il valide les identifiants standards (Email/Mot de passe) qui lui sont transmis en arrière-plan après la réussite de la biométrie locale.

---

## 🔄 2. Le Cycle de Vie en 3 Phases

L'intégration de la biométrie sur le frontend mobile se découpe en trois grandes étapes logiques.

### 📍 Phase A : L'Enrôlement (L'activation initiale)
Cette phase se produit uniquement la première fois que l'utilisateur décide d'activer la fonctionnalité.

1. **Connexion initiale :** L'utilisateur saisit son Email et son Mot de passe sur l'écran de connexion traditionnel.
2. **Appel API :** L'application envoie ces données au serveur qui valide et renvoie un Token d'accès API de courte durée (30 minutes).
3. **Proposition d'activation :** L'application affiche une pop-up : *"Souhaitez-vous activer la connexion rapide par empreinte digitale / Face ID ?"*.
4. **Vérification de la compatibilité :** L'application interroge le système pour savoir si l'appareil possède des capteurs biométriques actifs et configurés.
5. **Stockage Chiffré :** Si l'utilisateur accepte, l'application sauvegarde l'Email et le Mot de passe de l'utilisateur dans le **Secure Storage** (stockage sécurisé matériel du téléphone).
6. **Marqueur d'activation :** L'application enregistre une variable simple (ex: `biometrie_active = true`) dans ses préférences locales non chiffrées pour s'en souvenir au prochain démarrage.

---

### 📍 Phase B : L'Authentification Standard (Appels d'API)
C'est le fonctionnement quotidien de l'application.

1. L'application mobile effectue ses requêtes HTTP classiques vers le serveur Laravel.
2. Chaque requête inclut le Token d'accès API dans les en-têtes (Headers) de sécurité.
3. Tant que le Token est valide (pendant ses 30 minutes de vie), le serveur traite les requêtes normalement.

---

### 📍 Phase C : Le Renouvellement Silencieux (Le cœur du système)
C'est l'étape magique où l'intercepteur au niveau de l'application mobile intervient lors de l'expiration du Token.

1. **Expiration du Token :** L'utilisateur effectue une action après 30 minutes. Le serveur Laravel détecte que le Token a expiré et renvoie un code d'erreur HTTP **`401 Unauthorized`**.
2. **Interception :** L'intercepteur HTTP global du frontend mobile capture cette erreur 401.
3. **Mise en pause :** L'intercepteur met immédiatement en attente (dans une file d'attente logicielle en mémoire) la requête qui vient d'échouer ainsi que toutes les requêtes d'API suivantes.
4. **Déclenchement biométrique :** L'application mobile demande au système d'exploitation d'afficher la fenêtre de vérification biométrique.
5. **Authentification locale :** L'utilisateur pose son doigt ou regarde son écran.
6. **Lecture sécurisée :**
   * *Si succès :* Le système d'exploitation déverrouille l'accès au Secure Storage. L'application y lit l'Email et le Mot de passe stockés.
   * *Si échec (ou annulation) :* L'application mobile vide la file d'attente, efface les jetons et redirige proprement l'utilisateur vers l'écran de connexion traditionnel.
7. **Reconnexion invisible :** L'application mobile envoie silencieusement une requête de connexion traditionnelle (Email + Mot de passe) au serveur Laravel.
8. **Mise à jour :** Le serveur valide, génère un nouveau Token Sanctum tout neuf et le renvoie.
9. **Libération de la file d'attente :** L'application mobile enregistre le nouveau Token, l'applique dans les en-têtes de la requête initiale mise en attente, et **re-soumet la requête** automatiquement. 

*Pour l'utilisateur, l'écran a simplement scintillé une fraction de seconde avec la pop-up Face ID / Empreinte, et son action s'est poursuivie normalement sans perte de données.*

---

## ⚠️ 3. Gestion des Cas Limites et Sécurité Critique

Pour garantir une expérience utilisateur sans faille et une sécurité digne d'une application bancaire, le frontend doit obligatoirement gérer les cas suivants :

### 1. Modification des données biométriques de l'appareil (Alerte de Sécurité Majeure)
* **Le risque :** Un voleur subtilise le téléphone déverrouillé d'un utilisateur, ajoute sa propre empreinte dans les réglages du téléphone, puis ouvre votre application. Si l'application ne vérifie rien, le voleur accède au compte.
* **La solution :** Les systèmes iOS et Android génèrent un identifiant unique basé sur l'état de la base de données biométrique de l'appareil (Invalidation sur changement de clés). Si l'utilisateur ajoute ou supprime une empreinte sur son téléphone, le Secure Storage de votre application s'auto-détruit ou devient inaccessible. Le frontend doit détecter ce cas, afficher un message d'avertissement de sécurité, et forcer une reconnexion manuelle complète par mot de passe pour réactiver la biométrie.

### 2. Capteur indisponible ou désactivé
* L'utilisateur peut désactiver la biométrie dans les réglages de son téléphone à tout moment.
* **La solution :** Au démarrage de l'application, vérifiez toujours la disponibilité du matériel. Si le matériel n'est plus disponible ou configuré, masquez les options biométriques et proposez la saisie classique du mot de passe.

### 3. Trop de tentatives infructueuses localement
* Si l'utilisateur tente de déverrouiller l'appareil avec un doigt humide ou erroné plusieurs fois, le système d'exploitation bloque temporairement le capteur biométrique.
* **La solution :** L'API du téléphone renverra une erreur spécifique (ex: "Bloqué pour cause de tentatives trop nombreuses"). L'application mobile doit immédiatement basculer automatiquement sur la méthode de secours (saisie du mot de passe complet).

---

## 📈 4. Schéma conceptuel du parcours utilisateur (UX Flow)

```
[ Connexion réussie via Email/Password ]
                   │
                   ▼
     [ Activer la biométrie ? ] ───( Non )───► [ Navigation classique ]
                   │
                 ( Oui )
                   │
                   ▼
  [ Stocker Email/Password chiffrés ]
                   │
                   ▼
   [ Utilisation (Token valide: 30 min) ]
                   │
        ( Expiration du Token )
                   │
                   ▼
        [ Erreur HTTP 401 ]
                   │
                   ▼
   [ Affichage Pop-up Empreinte/FaceID ]
                   │
         ┌─────────┴─────────┐
      ( Succès )          ( Échec / Annulé )
         │                   │
         ▼                   ▼
[ Lire les identifiants ]  [ Redirection vers Écran Login ]
         │
         ▼
[ POST /api/login silencieux ]
         │
         ▼
[ Nouveau Token reçu & Requêtes rejouées ]
```
