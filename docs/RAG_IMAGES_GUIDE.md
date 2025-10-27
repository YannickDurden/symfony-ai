## 🤔 Qu'est-ce que la recherche sémantique d'images ?

### Définition simple

La **recherche sémantique d'images** permet de trouver des images en utilisant des **descriptions en langage naturel** plutôt que des mots-clés exacts.

Au lieu de chercher par nom de fichier ou tags, vous pouvez chercher par **ce que l'image représente**.

### Analogie du monde réel

**Recherche traditionnelle (par nom de fichier) :**
- Vous : "Cherche 'IMG_1234.jpg'"
- Système : "Voici IMG_1234.jpg"
- ❌ Vous devez connaître le nom exact

**Recherche sémantique (par description) :**
- Vous : "Cherche des images avec des gens autour d'une table"
- Système : *[Analyse le contenu de toutes les images]*
- Système : "Voici 5 images de personnes réunies autour d'une table"
- ✅ Vous décrivez ce que vous cherchez !

---

## 🔍 Comment ça fonctionne ?

### Les 4 étapes du système

```
┌─────────────────────────────────────────────────────────────┐
│              1. ANALYSE DES IMAGES (GPT-4o-mini)            │
│  (Préparation : on décrit chaque image avec l'IA)           │
└─────────────────────────────────────────────────────────────┘
                            ↓
    Images → GPT-4o-mini → Descriptions textuelles → PostgreSQL


┌─────────────────────────────────────────────────────────────┐
│                    2. INDEXATION (RAG)                      │
│  (On transforme les descriptions en vecteurs)               │
└─────────────────────────────────────────────────────────────┘
                            ↓
    Descriptions → Vecteurs → Stockage dans ChromaDB


┌─────────────────────────────────────────────────────────────┐
│                    3. RECHERCHE                             │
│  (Quand l'utilisateur pose une question)                    │
└─────────────────────────────────────────────────────────────┘
                            ↓
    Question → Vecteur → Recherche de similarité → Images pertinentes


┌─────────────────────────────────────────────────────────────┐
│                    4. AFFICHAGE                             │
│  (Affichage des images en grille Instagram)                 │
└─────────────────────────────────────────────────────────────┘
                            ↓
        Images trouvées → Grille responsive → Utilisateur
```
---

### Les 4 services principaux

| Service | Rôle                                               | Fichier |
|---------|----------------------------------------------------|---------|
| **ImageAnalyzer** | Analyse les images avec GPT-4o-mini                | `src/Service/Image/ImageAnalyzer.php` |
| **ImageStore** | Gère le stockage ChromaDB et prépare les documents | `src/Service/Image/ImageStore.php` |
| **ImageIndexer** | Transforme les descriptions en vecteurs            | `src/Service/Image/ImageIndexer.php` |
| **ImageSearch** | Effectue les recherches sémantiques                | `src/Service/Image/ImageSearch.php` |

---

## 🧩 Les composants en détail

### 1. ImageAnalyzer - L'analyseur d'images avec GPT-4o-mini

**Rôle :** Analyser les images et générer des descriptions détaillées.

**Code clé :**
```php
public function analyze(string|File $image): ?string
{
    $platform = $this->openAiPlatform();
    $messages = new MessageBag(
        Message::forSystem($this->getForSystemPrompt()),
        Message::ofUser(
            $this->getAnalysisPrompt(),
            Image::fromFile($imagePath)  // ← Envoie l'image à GPT-4o-mini
        )
    );

    return $platform
        ->invoke('gpt-4o-mini', $messages)
        ->asText(); // ← Retourne la description textuelle
}
```

---

### 2. ImageStore - Le gestionnaire de stockage

**Rôle :** Gérer la connexion à ChromaDB et préparer les documents pour l'indexation.

**Code clé :**
```php
$this->store = new ChromaStore(
    client: $client,
    collectionName: 'images',  // ← Collection dédiée aux images
);
```

**Que fait `prepareDocuments()` ?**

Transforme les entités `Image` en objets `TextDocument` :

```php
public function prepareDocuments(array $images): array
{
    return array_map(
        static fn (Image $image) => new TextDocument(
            id: Uuid::fromString($image->getUuid()),
            content: $image->getDescription(),  // ← Description GPT-4o-mini
            metadata: new Metadata([
                'filename' => $image->getFilename(),
                'path' => $image->getPath(),
                'postgres_id' => $image->getId(),  // ← Clé pour retrouver l'image
            ])
        ),
        $images
    );
}
```
**Pourquoi `postgres_id` dans les metadata ?**

Après la recherche vectorielle, on utilise `postgres_id` pour récupérer l'entité `Image` complète depuis PostgreSQL.

---

### 3. ImageIndexer - Le transformateur en vecteurs

**Rôle :** Transformer les descriptions d'images en vecteurs et les stocker dans ChromaDB.

**Les étapes de l'indexation :**

```php
public function indexDocuments(array $documents): void
{
    // 1. Créer un Indexer
    $indexer = new Indexer(
        loader: new InMemoryLoader($documents),  // Charge les documents
        vectorizer: $this->vectorizer(),         // Transforme en vecteurs
        store: $this->store->getStore()          // Stocke dans ChromaDB
    );

    // 2. Lancer l'indexation
    $indexer->index($documents);
}
```

**Que se passe-t-il pendant `index()` ?**

```
Pour chaque image :
1. Prend la description : "Une famille de quatre personnes..."
2. Envoie à OpenAI (text-embedding-3-small)
3. Reçoit un vecteur : [0.234, -0.456, 0.789, ..., 0.123] (1536 dimensions)
4. Stocke dans ChromaDB : {uuid, vecteur, metadata}
```

**Le Vectorizer :**

```php
public function vectorizer(): Vectorizer
{
    return new Vectorizer(
        platform: $this->openAiPlatform(),
        model: 'text-embedding-3-small'  // ⚠️ Même modèle partout !
    );
}
```

⚠️ **Important :** Utilisez toujours le même modèle d'embedding pour indexer ET chercher !

---

### 4. ImageSearch - Le moteur de recherche sémantique

**Rôle :** Effectuer des recherches sémantiques et retourner les images pertinentes.

**Différence avec MoviesSearch :**

| Aspect | MoviesSearch | ImageSearch |
|--------|--------------|-------------|
| **Utilise Agent** | ✅ Oui (GPT-4o-mini) | ❌ Non |
| **Retour** | `string` (réponse texte) | `Image[]` (entités) |
| **Coût** | 💰💰 Élevé (2 appels API) | 💰 Faible (1 appel API) |
| **Cas d'usage** | Conversation, recommandation | Affichage visuel |

**Code de la recherche :**

```php
public function query(string $question, int $limit = 10): array
{
    // 1. Recherche vectorielle dans ChromaDB
    $documents = $this->store->getStore()->query(
        vector: $this->vectorizer()->vectorize($question),
        limit: $limit
    );

    // 2. Convertir les documents en entités Image
    $images = [];
    foreach ($documents as $document) {
        $postgresId = $document->metadata['postgres_id'] ?? null;
        if ($postgresId) {
            $image = $this->imageRepository->find($postgresId);
            if ($image instanceof Image) {
                $images[] = $image;
            }
        }
    }

    return $images;
}
```

**Flux détaillé :**

```
Question : "des gens autour d'une table"
    ↓
1. Vectorization (OpenAI)
   → [0.234, -0.456, 0.789, ...]
    ↓
2. Recherche dans ChromaDB
   → Documents similaires avec metadata
    ↓
3. Extraction des postgres_id
   → [42, 15, 8, 23, ...]
    ↓
4. Requête PostgreSQL
   → SELECT * FROM image WHERE id IN (42, 15, 8, 23)
    ↓
5. Retour des entités Image
   → [Image1, Image2, Image3, ...]
```

---

## 🔄 Flux complet d'une recherche

### Scénario : Un utilisateur cherche "une piscine"

```
┌─────────────────────────────────────────────────────────────┐
│ ÉTAPE 1 : L'utilisateur pose une question                  │
└─────────────────────────────────────────────────────────────┘

Utilisateur tape : "une piscine"
    ↓
SearchImages Component (LiveComponent)
    ↓
Appelle : ImageSearch->query("une piscine", limit: 12)


┌─────────────────────────────────────────────────────────────┐
│ ÉTAPE 2 : Vectorisation de la question                     │
└─────────────────────────────────────────────────────────────┘

"une piscine"
    ↓
Vectorizer envoie à OpenAI (text-embedding-3-small)
    ↓
Reçoit : [0.234, -0.456, 0.789, ..., 0.123]
    ↓
Coût : ~$0.00002


┌─────────────────────────────────────────────────────────────┐
│ ÉTAPE 3 : Recherche dans ChromaDB                          │
└─────────────────────────────────────────────────────────────┘

ChromaDB compare le vecteur avec tous les vecteurs stockés
    ↓
Calcul de similarité cosinus
    ↓
Trouve les 12 documents les plus similaires :

Document 1 (similarité: 0.92)
  - content: "Une grande piscine extérieure avec des transats..."
  - metadata: {filename: "pool_summer.jpg", postgres_id: 42}

Document 2 (similarité: 0.88)
  - content: "Une piscine intérieure dans un spa de luxe..."
  - metadata: {filename: "spa_pool.jpg", postgres_id: 15}

...


┌─────────────────────────────────────────────────────────────┐
│ ÉTAPE 4 : Récupération des entités Image                   │
└─────────────────────────────────────────────────────────────┘

Pour chaque document :
    Extrait postgres_id (42, 15, 8, ...)
    ↓
Requête PostgreSQL :
    SELECT * FROM image WHERE id IN (42, 15, 8, ...)
    ↓
Retourne les entités Image complètes avec toutes les propriétés


┌─────────────────────────────────────────────────────────────┐
│ ÉTAPE 5 : Affichage en grille Instagram                    │
└─────────────────────────────────────────────────────────────┘

SearchImages Component reçoit Image[]
    ↓
Affiche en grille responsive :
    - 3-4 colonnes sur desktop
    - 2-3 colonnes sur tablet
    - 1 colonne sur mobile
    ↓
Chaque image :
    - Ratio carré 1:1
    - Overlay au survol avec filename + description
    - Lazy loading
```
