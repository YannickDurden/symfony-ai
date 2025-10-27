## 🤔 Qu'est-ce qu'un RAG ?

### Définition simple

**RAG** signifie **Retrieval-Augmented Generation** (Génération Augmentée par Récupération).

C'est une technique qui permet à une Intelligence Artificielle de répondre à vos questions en se basant sur **vos propres documents** plutôt que uniquement sur ses connaissances générales.

### Analogie du monde réel

Imaginez que vous êtes dans une bibliothèque et vous posez une question à un bibliothécaire :

**Sans RAG :**
- Vous : "Quel film parle de la mafia ?"
- Bibliothécaire : "Je ne sais pas, je ne connais pas tous les films..."

**Avec RAG :**
- Vous : "Quel film parle de la mafia ?"
- Bibliothécaire : *[Cherche dans le catalogue de la bibliothèque]*
- Bibliothécaire : "D'après notre catalogue, 'Le Parrain' est un film qui parle de la mafia..."

Le RAG permet à l'IA de "chercher dans votre catalogue" avant de répondre !

---

## 🔍 Comment ça fonctionne ?

### Les 3 étapes du RAG

```
┌─────────────────────────────────────────────────────────────┐
│                    1. INDEXATION                            │
│  (Préparation : on met les films dans la "bibliothèque")    │
└─────────────────────────────────────────────────────────────┘
                            ↓
        Films → Vecteurs → Stockage dans ChromaDB


┌─────────────────────────────────────────────────────────────┐
│                    2. RECHERCHE                             │
│  (Quand l'utilisateur pose une question)                    │
└─────────────────────────────────────────────────────────────┘
                            ↓
    Question → Vecteur → Recherche de similarité → Films pertinents


┌─────────────────────────────────────────────────────────────┐
│                    3. GÉNÉRATION                            │
│  (L'IA génère une réponse basée sur les films trouvés)      │
└─────────────────────────────────────────────────────────────┘
                            ↓
        Films trouvés + Question → IA → Réponse personnalisée
```

### Concept clé : Les vecteurs numériques

**Qu'est-ce qu'un vecteur ?**

Un vecteur est une liste de nombres qui représente le **sens** d'un texte.

**Exemple :**
```
"Le Parrain est un film sur la mafia"
    ↓ (transformation)
[0.23, -0.45, 0.78, 0.12, ..., 0.56]  (1536 nombres)
```

**Pourquoi c'est puissant ?**

Les textes avec un sens similaire ont des vecteurs proches :

```
"Film sur la mafia"     → [0.23, -0.45, 0.78, ...]
"Le Parrain"            → [0.25, -0.43, 0.76, ...]  ← PROCHE !
"Film de science-fiction" → [-0.67, 0.89, -0.23, ...] ← LOIN
```

Cela permet de trouver des documents pertinents même si les mots exacts ne correspondent pas !

---

## 🎯 Vue d'ensemble

Ce projet contient **deux systèmes de recherche** :

### 🎬 Recherche de Films
Recherche conversationnelle avec un Agent IA qui répond à vos questions sur les films de science-fiction.

**Exemple :**
```
Question : "Quel film d'action avec Bruce Willis ?"
Réponse : "Je vous recommande Die Hard (1988), un film d'action 
           emblématique avec Bruce Willis dans le rôle de John McClane..."
```

### 🖼️ Recherche d'Images
Recherche sémantique d'images par description naturelle avec affichage en grille Instagram.

**Exemple :**
```
Question : "des gens autour d'une table"
Résultat : [Affichage de 12 images de personnes réunies autour d'une table]
```
---

## 🚀 Démarrage rapide

### Prérequis

- DDEV
- PHP 8.2+
- Symfony 7.x
- Docker (pour ChromaDB)
- Clé API OpenAI

### Installation

1. **Cloner le projet**
   ```bash
   git clone <votre-repo>
   cd symfony-ai
   ddev start
   ```

2. **Installer les dépendances**
   ```bash
   ddev ssh
   composer install
   npm install
   npm run build
   ```

3. **Configurer l'environnement**
   ```bash
   cp .env .env.local
   ```
   
   Éditer `.env.local` :
   ```env
   OPENAI_API_KEY=sk-your-api-key
   ```

4. **Créer la base de données**
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

5. **Indexer les données**
   
   Pour les films :
   ```bash
   php bin/console rag:index:movies
   ```
   
   Pour les images :
   ```bash
   # 1. Générer les descriptions
   php bin/console rag:generate:image:description
   
   # 2. Indexer dans ChromaDB
   php bin/console rag:index:images
   ```

---

## 🏗️ Architecture

### Technologies utilisées

| Technologie | Rôle | Version |
|-------------|------|---------|
| **Symfony** | Framework PHP | 7.x |
| **Symfony AI** | Intégration IA | Latest |
| **OpenAI** | LLM et Embeddings | GPT-4o-mini, text-embedding-3-small |
| **ChromaDB** | Base de données vectorielle | Latest |
| **PostgreSQL** | Base de données relationnelle | 14+ |
| **Symfony UX LiveComponent** | Composants réactifs | Latest |
| **Docker** | Conteneurisation | Latest |

### Schéma global

```
┌─────────────────────────────────────────────────────────────┐
│                      UTILISATEUR                            │
└──────────────────────────┬──────────────────────────────────┘
                           │
                ┌──────────┴──────────┐
                ↓                     ↓
┌───────────────────────┐   ┌────────────────────┐
│  SearchMovies         │   │  SearchImages      │
│  (LiveComponent)      │   │  (LiveComponent)   │
└──────────┬────────────┘   └─────────┬──────────┘
           │                          │
           ↓                          ↓
┌───────────────────────┐   ┌────────────────────┐
│  MoviesSearch         │   │  ImageSearch       │
│  (avec Agent)         │   │  (sans Agent)      │
└──────────┬────────────┘   └─────────┬──────────┘
           │                          │
           └──────────┬───────────────┘
                      ↓
           ┌──────────────────┐
           │   OpenAI API     │
           │  - GPT-4o-mini   │
           │  - Embeddings    │
           └─────────┬────────┘
                     │
           ┌─────────┴────────┐
           ↓                  ↓
┌──────────────────┐   ┌──────────────────┐
│   ChromaDB       │   │   PostgreSQL     │
│ (Vecteurs)       │   │ (Données)        │
└──────────────────┘   └──────────────────┘
```

---

## 📊 Comparaison : Films vs Images

| Aspect | Films 🎬 | Images 🖼️            |
|--------|---------|-----------------------|
| **Source de données** | JSON statique | Fichiers images       |
| **Analyse initiale** | Aucune (données structurées) | GPT-4o-mini           |
| **Type de recherche** | Avec Agent (conversationnel) | Directe (vectorielle) |
| **Retour** | `string` (réponse texte) | `Image[]` (entités)   |
| **Cas d'usage** | Q&A, recommandation | Galerie, portfolio    |

---
