# WooCommerce Unit of Measure Step 2025

Ez a WordPress/WooCommerce bővítmény termékenként beállítható mennyiségi szabályokat ad: minimum, maximum, lépcsőköz, valamint opcionális tizedes pontosság. A beállítások betartását a Blocksy sablon mennyiségválasztójával is összehangolja, és kedves üzenetet jelenít meg, ha a rendszer a megadott értéket automatikusan módosította.

## Főbb funkciók

- Termékszintű minimum és maximum rendelési mennyiség.
- Rugalmas mennyiségi lépcsőfok, tizedes pontosság opcionális megadásával.
- A Blocksy `ct-increase`/`ct-decrease` gombjai a legközelebbi érvényes értékre ugranak.
- Automatikus korrekció a termékoldalon és a kosárban, személyre szabható értesítő üzenettel (nincs felugró ablak).
- WooCommerce import/export támogatás önállóan választható oszlopokkal.

## Beállítások és használat

1. Aktiváld a bővítményt a WordPress adminban.
2. A WooCommerce termék szerkesztőjében az **Készlet** fülön állítsd be a minimumot, maximumot, lépcsőfokot, valamint azt, hogy engedélyezett-e a tizedes pontosság és hány tizedesjegyig.
3. A WooCommerce menüben megjelenő **Mennyiségi szabályok** oldalon módosíthatod az automatikus korrekció üzenetét (`{product}`, `{requested}`, `{quantity}` helyettesítőkkel).
4. Az import/export során az egyedi oszlopok külön-külön kiválaszthatók.

## Követelmények

- WordPress 5.8+
- WooCommerce
- PHP 7.4+

A bővítmény a WordPress és a Blocksy sablon beépített mennyiség mezőire épít, így nincs szükség további sablonmódosításra.
