# Système de Prix Dynamiques - Documentation Complète

## Guide Fonctionnel

### Vue d'Ensemble du Système
Le système gère automatiquement les prix des produits en fonction de la localisation du client. Il prend en compte :
- La zone de livraison du client
- Les frais de transport
- Les tarifs spéciaux pour les grosses commandes
- Les taxes et écotaxes applicables

### Comment Fonctionne le Système ?

#### 1. Identification de la Zone Client
- Une popup demande le code postal au client
- Le système identifie automatiquement la zone de livraison
- Les prix s'adaptent instantanément selon la zone

#### 2. Calcul des Prix
Le prix affiché inclut :
- Le prix de base du produit
- Les frais de livraison (si applicable)
- L'écotaxe (pour les produits concernés)
- Les réductions quantitatives (pour les big bags)

#### 3. Cas Particuliers

##### Département 13
- Traitement spécial pour les livraisons dans le 13
- Certains produits uniquement disponibles dans cette zone
- Prix et conditions spécifiques

##### Big Bags
- Prix dégressifs automatiques selon la quantité
- Optimisation par lots de 8 big bags
- Affichage des économies possibles

##### Restrictions Géographiques
- Certains produits peuvent être limités à des zones spécifiques
- Message clair si un produit n'est pas disponible dans votre zone
- Suggestions alternatives si possible

### Points Importants à Comprendre
1. Les prix incluent toujours tous les coûts (pas de surprises)
2. Le système s'adapte en temps réel aux modifications
3. Les prix sont sauvegardés pour plus de rapidité
4. Le système gère automatiquement les mises à jour

---

## Guide Technique (Pour Développeurs)

### Architecture du Système

#### 1. Structure des Classes

```php
KM_Shipping_Zone
├── Gestion des zones de livraison
├── Validation des codes postaux
└── Association zones/produits

KM_Big_Bag_Manager
├── Calcul prix dégressifs
├── Gestion panier
└── Règles spéciales big bags

KM_Dynamic_Pricing
├── Calcul prix par zone
├── Gestion écotaxe
└── Cache et optimisation
```

### Détails Techniques par Composant

#### 1. KM_Shipping_Zone

##### Stockage des Données
```php
private $shipping_zones_cache = array();
private $product_shipping_class_cache = array();
private $shipping_zone_name_cache = array();
```

##### Méthodes Critiques
```php
public function get_shipping_zone_id_from_postcode($postcode) {
    // Recherche dans les zones avec plages de codes postaux
    // Utilise le cache pour optimiser les performances
    // Retourne l'ID de la zone ou null
}
```

##### Gestion des Cookies
- Format du cookie : `zip_code = "XXXXX-FR"`
- Durée de validité : 30 jours
- Sécurisation : Données sanitizées

#### 2. KM_Big_Bag_Manager

##### Calcul des Prix Dégressifs
```php
public function calculate_big_bags_shipping_price($product_id, $quantity, $base_price) {
    $lots = floor($quantity / 8);
    $reste = $quantity % 8;
    
    // Prix pour les lots complets
    $prix_lots = $lots * $this->get_lot_price($product_id);
    
    // Prix pour le reste
    $prix_reste = $reste * $this->get_unit_price($product_id, $reste);
    
    return $prix_lots + $prix_reste;
}
```

##### Optimisation du Panier
- Écoute de l'événement `woocommerce_before_calculate_totals`
- Mise à jour dynamique des prix
- Gestion du cache pour éviter les recalculs

#### 3. KM_Dynamic_Pricing

##### Système de Cache
```php
private $calculated_prices = array();
private $cached_results = array();
```

##### Transactions SQL
```php
private function recalculate_prices_for_product_and_zones($product) {
    global $wpdb;
    
    $wpdb->query('START TRANSACTION');
    try {
        // Mise à jour des prix pour chaque zone
        foreach ($zones as $zone) {
            $this->update_price_for_zone($product, $zone);
        }
        $wpdb->query('COMMIT');
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        $this->log_error($e);
    }
}
```

### Points d'Attention pour les Développeurs

#### 1. Gestion du Cache
- Utiliser `wp_cache_get/set` pour les données fréquemment accédées
- Vider le cache lors des mises à jour de prix
- Attention aux conflits de cache avec WooCommerce

#### 2. Optimisation des Performances
```php
// Exemple d'optimisation avec mise en cache
private function get_product_metadata($product) {
    $cache_key = 'product_metadata_' . $product->get_id();
    $metadata = wp_cache_get($cache_key, 'products');
    
    if (false === $metadata) {
        $metadata = $this->fetch_metadata($product);
        wp_cache_set($cache_key, $metadata, 'products');
    }
    
    return $metadata;
}
```

#### 3. Hooks et Filtres Importants
```php
// Hooks principaux à connaître
add_filter('woocommerce_product_get_price');
add_filter('woocommerce_get_price_html');
add_action('woocommerce_before_calculate_totals');
```

#### 4. Gestion des Erreurs
```php
private function handle_error($e, $context) {
    error_log(sprintf(
        '[KM_Dynamic_Pricing] Erreur dans %s: %s',
        $context,
        $e->getMessage()
    ));
    
    // Notification admin si critique
    if ($this->is_critical_error($e)) {
        $this->notify_admin($e);
    }
}
```

### Maintenance et Évolution

#### 1. Tests Recommandés
- Test des calculs de prix pour chaque zone
- Validation des codes postaux
- Vérification des performances avec grand volume
- Tests de charge sur le cache

#### 2. Surveillance
```php
// Exemple de métriques à surveiller
private function monitor_performance() {
    $metrics = array(
        'cache_hits' => $this->cache_hits,
        'cache_misses' => $this->cache_misses,
        'calculation_time' => $this->calculation_time
    );
    
    // Envoi à votre système de monitoring
    $this->send_metrics($metrics);
}
```

#### 3. Modifications Futures Suggérées
- Ajout d'un système de log détaillé
- Amélioration du cache avec Redis
- Interface d'administration améliorée
- API pour les prix en temps réel

### Intégration avec AtoonextSync

#### 1. Processus de Synchronisation
```php
public function handle_sync_update($product_id) {
    // Vérification du flag de sync
    if (!$this->is_sync_needed($product_id)) {
        return;
    }
    
    // Recalcul des prix
    $this->recalculate_prices($product_id);
    
    // Nettoyage
    $this->clear_sync_flag($product_id);
}
```

#### 2. Gestion des Conflits
- Priorité aux mises à jour manuelles
- Verrouillage pendant les calculs
- Journal des modifications

Ce document continuera d'évoluer avec le projet. N'hésitez pas à contribuer et à le maintenir à jour.

---
