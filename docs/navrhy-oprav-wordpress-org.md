# Návrhy oprav pro WordPress.org Plugin Review

Plugin: **WPA9 Gallery** 1.3.0  
Review ID: `R wpa9-gallery/aloziak/18Jun26/T2 21Jun26/4.0.1`  
Zdroj recenze: `docs/comments-wp.md`

Tento dokument je návrh. **Kód se nemění**, dokud se návrhy neschválí k implementaci.

Recenzenti výslovně chtějí: opravit **všechny výskyty** stejného problému, nejen uvedené příklady; otestovat na čisté instalaci s `WP_DEBUG`; nahrát nový ZIP a odpovědět **stručně** (bez výpisu změn).

Plugin Check (`wordpress.org/plugins/plugin-check/`) a PHPCS + WPCS spustit před dalším odesláním.

---

## Stav vůči recenzi

| # | Bod recenze | Stav v kódu 1.3.0 | Závažnost |
|---|-------------|-------------------|-----------|
| 1 | Enqueue JS/CSS (`<script>`) | Inline `<script>` v export/import, další inline JS | Blokující |
| 2 | Zápis PHP do `uploads/` | `index.php` s PHP kódem | Blokující |
| 3 | Vysoká pozice admin menu (25) | `add_menu_page(..., 25)` | Blokující |
| 4 | Sanitize / escape / validate | `wp_unslash()` bez `sanitize_*` u POST/GET | Blokující |
| 5 | SQL bez `$wpdb->prepare()` | Min. 11 výskytů, reálně více | Blokující |
| 6 | Contributors v `readme.txt` | Stále `wpa9`, ne `aloziak` | Blokující (vlastnictví) |
| 7 | `load_plugin_textdomain()` | Stále voláno | Doporučené |

---

## 1. Enqueue JS a CSS

### Co recenze vytýká

Plugin nesmí vypisovat `<script>` / `<style>` přímo v HTML. Použít `wp_enqueue_script()`, `wp_enqueue_style()`, `wp_add_inline_script()`, `wp_add_inline_style()`.

Příklad z recenze: `admin/views/export-import.php:126`.

### Nalezené výskyty

**A. Inline `<script>` (přesně to, co recenze jmenuje)**

`admin/views/export-import.php` řádky 126–168 — dropzóna pro JSON import.

**Návrh:** přesunout skript do `admin/js/admin.js` (nebo nového `admin/js/export-import.js`) a registrovat ho v `WPA9_Admin::enqueue_assets()`.

Podmíněné načtení jen na stránce `wpa9-export-import`:

```php
$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
if ( 'wpa9-export-import' === $page ) {
    wp_enqueue_script(
        'wpa9-export-import',
        WPA9_URL . 'admin/js/export-import.js',
        array(),
        WPA9_VERSION,
        true
    );
}
```

HTML dropzóny nechat; IDs (`wpa9-import-dropzone`, `wpa9-import-file`, …) zůstanou. Inline styly `dropzone.style.borderColor = …` nahradit CSS třídami v `admin/css/admin.css` (např. `.wpa9-upload-zone.is-dragover`).

**B. Inline event handlery (stejný princip, recenze je nejmenuje, Plugin Check je chytí)**

| Soubor | Handler |
|--------|---------|
| `admin/views/gallery-edit.php:119` | `onclick="this.select()"` |
| `admin/views/galleries-list.php:43` | `onsubmit="return confirm(...)"` |
| `admin/views/albums-list.php:36` | totéž |
| `admin/views/import.php:54` | totéž |

**Návrh:**

- Shortcode input: třída `wpa9-select-on-focus` a v `admin.js`:

```js
$(document).on('focus click', '.wpa9-select-on-focus', function () {
    this.select();
});
```

- Confirm u mazání / importu: `data-wpa9-confirm="<?php echo esc_attr( __( '…', 'wpa9-gallery' ) ); ?>"` a v `admin.js`:

```js
$(document).on('submit', 'form[data-wpa9-confirm]', function (e) {
    if ( ! window.confirm( $(this).attr('data-wpa9-confirm') ) ) {
        e.preventDefault();
    }
});
```

**C. Inline `<style>` ve frontend šabloně**

`templates/gallery-native.php` řádky 41–62 — dynamické CSS podle počtu sloupců (kopie jádra `gallery_shortcode()`).

**Návrh:** CSS sestavit v PHP a přidat přes `wp_add_inline_style( 'wpa9-gallery', $css )` v `WPA9_Shortcode::render_gallery()` **před** renderem šablony. Šablona `<style>` nevypisuje.

`wp_add_inline_style()` lze volat opakovaně (více galerií na stránce) — každý selektor `#gallery-{id}` je unikátní.

Atributy `style="--wpa9-cols: …"` v masonry/album šablonách jsou CSS custom properties, ne `<style>` tag. Recenze je typicky nechává; přesun do enqueue je volitelný.

Admin enqueue (`wpa9-admin.css` / `wpa9-admin.js`) a frontend `wp_register_style( 'wpa9-gallery' )` jsou v pořádku.

---

## 2. Zápis PHP souborů do `uploads/`

### Co recenze vytýká

`includes/class-wpa9-install.php:218` zapisuje do `wp-content/uploads/wpa9-galleries/index.php` obsah `<?php // Silence is golden.`.

Psát **kódové** (PHP) soubory mimo plugin složku recenze nepovoluje. Účel (zákaz listingu adresáře) je legitimní, forma ne.

Stejný vzor je i v `includes/class-wpa9-storage.php:49–54` u každé galerie a `thumbs/`.

### Návrh

Zapisovat **prázdný** `index.html` (žádné PHP):

```php
private static function protect_dir( $dir ) {
    if ( ! is_dir( $dir ) ) {
        return;
    }
    $index = trailingslashit( $dir ) . 'index.html';
    if ( ! file_exists( $index ) ) {
        file_put_contents( $index, '' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- empty anti-listing stub
    }
}
```

Použít v:

- `WPA9_Install::ensure_base_dir()`
- `WPA9_Storage::create_gallery_dir()` (galerie + thumbs)

Migrace: při `maybe_upgrade()` smazat staré `index.php` v `wpa9-galleries/` a nahradit `index.html`. Staré PHP soubory v `uploads/` by recenzenti mohli považovat za přetrvávající problém na existujících webech.

**Neměnit:** ukládání obrázků do `wp-content/uploads/wpa9-galleries/` — to recenze naopak doporučuje (`wp_upload_dir()`).

**Do e-mailu recenzentům** (jedna věta): soubory galerie zůstávají v `uploads/wpa9-galleries/`; místo PHP `index.php` se zapisuje prázdný `index.html`.

---

## 3. Pozice admin menu

### Co recenze vytýká

`add_menu_page( …, 25 )` v `includes/class-wpa9-admin.php:20`.

Pozice 25 je **Comments**. Plugin tím sedí vedle jádrových položek.

Jádro: Posts 5, Media 10, Pages 20, Comments 25, Appearance 60, Plugins 65, Users 70, Tools 75, Settings 80.

### Návrh

Top-level menu má smysl (více podstránek: Dashboard, Galleries, Albums, Settings, Import, Export). Stačí posunout níž.

**Doporučení:** pozice `58` (za Comments, před Appearance) — běžná volba u schválených pluginů.

```php
add_menu_page(
    __( 'WPA9 Gallery', 'wpa9-gallery' ),
    __( 'WPA9 Gallery', 'wpa9-gallery' ),
    self::CAP,
    self::DASHBOARD_SLUG,
    array( $this, 'page_dashboard' ),
    'dashicons-format-gallery',
    58
);
```

Alternativa: vynechat 7. argument (default ~99, úplně dole). Recenze: „Use a lower menu position if a top-level item is necessary.“

Nepřesouvat pod Settings/Tools — zbytečně by se rozbila navigace.

---

## 4. Sanitizace, validace, escapování

Mantra recenze: **Sanitize early, Escape late, Always Validate.** `wp_unslash()` ani `esc_*()` **není** sanitizace.

### 4.1 Příklady z recenze

**AJAX meta** — `includes/class-wpa9-ajax.php:173–175`

Teď:

```php
'caption' => isset( $_POST['caption'] ) ? wp_unslash( $_POST['caption'] ) : '',
```

`WPA9_Image::update_meta()` sice sanitizuje později, recenze chce sanitizaci **při čtení** `$_POST`.

```php
$data = array(
    'caption'     => isset( $_POST['caption'] )     ? sanitize_text_field( wp_unslash( $_POST['caption'] ) ) : '',
    'description' => isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '',
    'alt_text'    => isset( $_POST['alt_text'] )    ? sanitize_text_field( wp_unslash( $_POST['alt_text'] ) ) : '',
);
```

Sanitizaci v `update_meta()` ponechat (obrana v hloubce).

**Uložení galerie** — `includes/class-wpa9-admin.php:117–125`

```php
$data = array(
    'name'            => isset( $_POST['name'] )            ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
    'description'     => isset( $_POST['description'] )     ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '',
    'author_name'     => isset( $_POST['author_name'] )     ? sanitize_text_field( wp_unslash( $_POST['author_name'] ) ) : '',
    'fullsize_width'  => isset( $_POST['fullsize_width'] )  ? absint( $_POST['fullsize_width'] ) : 2560,
    'fullsize_height' => isset( $_POST['fullsize_height'] ) ? absint( $_POST['fullsize_height'] ) : 2560,
    'thumb_width'     => isset( $_POST['thumb_width'] )     ? absint( $_POST['thumb_width'] ) : 576,
    'thumb_height'    => isset( $_POST['thumb_height'] )    ? absint( $_POST['thumb_height'] ) : 576,
    'ratio'           => isset( $_POST['ratio'] )           ? sanitize_key( wp_unslash( $_POST['ratio'] ) ) : '',
    'data_atts'       => isset( $_POST['data_atts'] )       ? sanitize_textarea_field( wp_unslash( $_POST['data_atts'] ) ) : '',
    'sort_order'      => isset( $_POST['sort_order'] )      ? intval( $_POST['sort_order'] ) : 0,
);
```

`ratio` následně projít existujícím `WPA9_Gallery::sanitize_ratio()`.

**Notices v URL** — `includes/class-wpa9-admin.php:89–91`

Recenze: `esc_html( wp_unslash( $_GET['wpa9_notice'] ) )` nestačí — `esc_*` není sanitize.

Minimální oprava:

```php
$allowed_types = array( 'success', 'error', 'warning' );
$type          = isset( $_GET['wpa9_type'] ) ? sanitize_key( wp_unslash( $_GET['wpa9_type'] ) ) : 'success';
if ( ! in_array( $type, $allowed_types, true ) ) {
    $type = 'success';
}
$notice = isset( $_GET['wpa9_notice'] ) ? sanitize_text_field( wp_unslash( $_GET['wpa9_notice'] ) ) : '';
if ( $notice !== '' ) {
    printf(
        '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
        esc_attr( $type ),
        esc_html( $notice )
    );
}
```

**Lepší návrh (doporučený):** notices do transientu uživatele, v URL jen flag. Dlouhé přeložené věty v GET jsou křehké a recenzenti je nemají rádi.

```php
private function redirect_with_notice( $args, $message, $type = 'success' ) {
    set_transient(
        'wpa9_notice_' . get_current_user_id(),
        array(
            'message' => $message,
            'type'    => $type,
        ),
        30
    );
    wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
    exit;
}
```

`show_notices()` přečte a smaže transient. Query args `wpa9_notice` / `wpa9_type` odpadnou.

**Hidden `pending_import`** — `admin/views/export-import.php:92`

```php
$pending_key = isset( $_GET['pending_import'] ) ? sanitize_text_field( wp_unslash( $_GET['pending_import'] ) ) : '';
echo esc_attr( $pending_key );
```

(`sanitize_key()` zkrátí hash, pokud obsahuje znaky mimo `[a-z0-9_-]` — `wp_hash()` je MD5 hex, `sanitize_key` stačí, ale `sanitize_text_field` + `esc_attr` je bezpečnější vůči budoucí změně hashe.)

### 4.2 Další vstupy (stejný problém, recenze je nejmenuje)

Opravit **všechny** čtení `$_GET` / `$_POST` / `$_REQUEST` / `$_FILES` stejně.

| Místo | Návrh |
|-------|--------|
| `$_POST['wpa9_action']` | `sanitize_key( wp_unslash( $_POST['wpa9_action'] ) )` |
| `$_GET['page']` | `sanitize_key( wp_unslash( $_GET['page'] ) )` |
| `$_GET['id']` (views) | `absint( $_GET['id'] )` — OK, doplnit `wp_unslash` není nutný u čísla |
| `save_album` name/description | jako u galerie |
| `save_settings` rozměry | `isset()` + `absint()`; teď `(int) $_POST['fullsize_width']` bez `isset` → notice v PHP 8 |
| `$_POST['ids']` / `gallery_ids` | `array_map( 'absint', wp_unslash( (array) $_POST['ids'] ) )` |
| `$_GET['imported']` | `isset( $_GET['imported'] ) && absint( wp_unslash( $_GET['imported'] ) )` |
| `$_FILES['file']['name']` | `sanitize_file_name( wp_unslash( $file['name'] ) )` + `wp_check_filetype_and_ext()` místo pouhého `wp_check_filetype()` |
| `$_FILES['import_file']` | ověřit `is_uploaded_file( $file['tmp_name'] )`; MIME/přípona `.json` |

Nonce už sanitizovaný je (`class-wpa9-ajax.php:22`).

### 4.3 Upload souborů

Teď: `copy( $file['tmp_name'], $target )` po `is_uploaded_file()`.

Recenze u médií preferuje `wp_handle_upload()` / `wp_handle_sideload()`. Galerie úmyslně není Media Library — `wp_handle_upload()` s dočasným filtrem `upload_dir` je kompromis, který recenzenti berou:

```php
$slug = $gallery->slug;
$filter = static function ( $dirs ) use ( $slug ) {
    $subdir = '/wpa9-galleries/' . $slug;
    $dirs['subdir'] = $subdir;
    $dirs['path']   = $dirs['basedir'] . $subdir;
    $dirs['url']    = $dirs['baseurl'] . $subdir;
    return $dirs;
};
add_filter( 'upload_dir', $filter );
$uploaded = wp_handle_upload(
    $_FILES['file'],
    array(
        'test_form' => false,
        'mimes'     => array(
            'jpg|jpeg|jpe' => 'image/jpeg',
            'png'          => 'image/png',
            'gif'          => 'image/gif',
            'webp'         => 'image/webp',
            'avif'         => 'image/avif',
        ),
    )
);
remove_filter( 'upload_dir', $filter );
```

Pokud `wp_handle_upload` kvůli custom adresáři komplikuje thumbs, stačí aspoň `wp_check_filetype_and_ext()` + `sanitize_file_name` a nechat `copy()`. V e-mailu vysvětlit, že soubory nejsou v Media Library.

---

## 5. SQL a `$wpdb->prepare()`

### Co recenze vytýká

Interpolace `$t['gallery']` / `$t['pictures']` do SQL. Plugin vyžaduje WP **6.5** → lze použít placeholder `%i` (identifikátory, od WP 6.2).

Recenze: 11 výskytů. V kódu jich je víc — opravit **všechny**.

### Pravidlo

Jakákoli dynamická část SQL (i `$wpdb->prefix . '…'` nebo `self::table()`) jde do `prepare()`. Názvy tabulek: `%i`. Hodnoty: `%d` / `%s`. Klauzule `IN (…)`: dynamické placeholdery, ne `implode( ',', array_map( 'intval', … ) )` bez `prepare()`.

### Konkrétní místa

**`includes/class-wpa9-importer-nextgen.php`** (příklady z recenze)

```php
// COUNT
$out['galleries'] = (int) $wpdb->get_var(
    $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $t['gallery'] )
);

// SELECT *
$ngg_galleries = $wpdb->get_results(
    $wpdb->prepare( 'SELECT * FROM %i ORDER BY gid ASC', $t['gallery'] )
);

// IN (...)
$placeholders = implode( ',', array_fill( 0, count( $gids ), '%d' ) );
$sql          = $wpdb->prepare(
    "SELECT * FROM %i WHERE galleryid IN ($placeholders) ORDER BY galleryid ASC, sortorder ASC, pid ASC",
    array_merge( array( $t['pictures'] ), $gids )
);
```

Stejně: COUNT pictures/album (ř. 47, 50), SELECT albums (ř. 176).

**`includes/class-wpa9-gallery.php`**

- ř. 18–24 JOIN — `%i` pro `$tbl` i `$j`, `%d` pro `album_id`
- ř. 26 `all()` bez filtru — `prepare( 'SELECT * FROM %i ORDER BY sort_order ASC, name ASC', $tbl )`
- ř. 33, 42, 47, 135, 236 — table name přes `%i`

**`includes/class-wpa9-album.php`**

- ř. 15 `all()`
- ř. 20, 25, 75, 85, 102, 113, 116, 140

**`includes/class-wpa9-image.php`**

- ř. 15, 20–22, 28–30

**`includes/class-wpa9-exporter.php`**

- ř. 20, 25, 30, 35 — čtyři `SELECT * FROM {$wpdb->prefix}wpa9_*`

**`includes/class-wpa9-install.php`**

- ř. 64–66 SELECT při migraci `album_id`
- ř. 68–71 INSERT už `prepare` má, tabulka `$junction_t` stále interpolovaná → `%i`
- ř. 73 `ALTER TABLE {$galleries_t} DROP COLUMN album_id` → `$wpdb->prepare( 'ALTER TABLE %i DROP COLUMN album_id', $galleries_t )`

**`uninstall.php`**

```php
foreach ( $tables as $t ) {
    $wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $t ) );
}
```

`$wpdb->insert()` / `update()` / `delete()` `prepare` nepotřebují.

---

## 6. Contributors a vlastnictví

Automatická recenze: contributor `wpa9` ≠ username `aloziak`; e-mail `gmail.com` vs. Plugin URI `apollo1.cz`.

V kódu 1.3.0 je **stále**:

```
Contributors: wpa9
```

v `readme.txt` i `README.md`. Mail z 21. 6. 2026 tvrdí opak — ZIP s touto opravou zjevně není aktuální strom.

### Návrh

```
Contributors: aloziak
```

v **obou** readme souborech.

E-mail na WordPress.org profilu: `info@apollo1.cz` (už zmíněno v odpovědi). Po nahrání ZIP v odpovědi **jednou větou** potvrdit: účet `aloziak`, contributor `aloziak`, profilový e-mail na doméně `apollo1.cz`.

Neresubmitovat z jiného účtu.

---

## 7. `load_plugin_textdomain()`

`wpa9-gallery.php:45`. Plugin vyžaduje WP 6.5 → na wordpress.org se překlady načtou podle text domain `wpa9-gallery` samy.

**Návrh:** volání odstranit. Složku `languages/` lze nechat pro vývoj / off-directory instalace; na.org ji překryje translate.wordpress.org.

---

## Další nálezy mimo recenzi

Recenzenti píší, že příště projdou **celý** plugin. Tyto body stojí za opravu ve stejném cyklu.

### A. Špatný počet obrázků na Export/Import

`admin/views/export-import.php:8`:

```php
$images = WPA9_Image::for_gallery( null );
```

`for_gallery()` dělá `(int) $gallery_id` → `0` → vždy 0 obrázků.

**Návrh:** `WPA9_Image::count_all()`:

```php
public static function count_all() {
    global $wpdb;
    return (int) $wpdb->get_var(
        $wpdb->prepare( 'SELECT COUNT(*) FROM %i', self::table() )
    );
}
```

### B. Import ignoruje `sort_order` u vazeb album–galerie

`WPA9_Importer::import()` volá:

```php
WPA9_Album::add_gallery( $new_album_id, $new_gallery_id, $ag['sort_order'] ?? 0 );
```

`add_gallery()` má jen 2 argumenty — třetí se zahodí.

**Návrh:** třetí parametr `$sort_order = null` v `add_gallery()`, nebo po importu `set_gallery_ids()` s pořadím z JSON.

### C. Potlačení chyb `@`

Plugin Check často flaguje `@unlink`, `@chmod`, `@file_put_contents`, `@rename`, `@scandir`, `@copy`, `@getimagesize`, `@rmdir`.

**Návrh:** místo `@` kontrolovat návratovou hodnotu / `file_exists()`. `set_time_limit()` nechat s `@` nebo `function_exists` — na některých hostinzích je v `disable_functions`.

### D. Export JSON přes `header()` + `echo`

`handle_post()` case `export_wpa9`: surové HTTP hlavičky a `echo $json`.

**Návrh:** `nocache_headers()`; `echo` JSON, který plugin sám vygeneroval (`wp_json_encode`), je v pořádku. Alternativa: `admin-post.php` endpoint. Nepoužívat `wp_kses` na JSON.

### E. `flush_rewrite_rules()` bez rewrite rules

`WPA9_Install::activate()` / `deactivate()` flushují rewrite rules, plugin žádné neregistruje. Zbytečné (pomalá aktivace). Odstranit.

### F. Masonry: HTML uvnitř `esc_html__()`

`templates/gallery-masonry.php:36`:

```php
printf( esc_html__( 'Photos by %s', 'wpa9-gallery' ), '<span>' . esc_html( $gallery->author_name ) . '</span>' );
```

Překlad se escapuje, `<span>` se vloží přes `%s` — záměr OK, PHPCS to občas hlásí. Čistší:

```php
echo esc_html__( 'Photos by', 'wpa9-gallery' ) . ' <span>' . esc_html( $gallery->author_name ) . '</span>';
```

(spojit do jedné translatable šablony s `wp_kses` povoleným `<span>`.)

### G. README vs. kód

`README.md` pořád říká „galleries can belong to one album“, kód je many-to-many. Upravit před zveřejněním.

Changelog 1.3.0: překlepy (`regenrate`).

---

## Doporučené pořadí implementace

1. `readme.txt` / `README.md` — Contributors: `aloziak`
2. Menu pozice 58
3. Odstranit `load_plugin_textdomain()`
4. `index.html` místo PHP `index.php` v uploads + úklid starých souborů
5. Enqueue: export-import JS, confirm/select, inline CSS native gallery
6. Sanitize všech `$_GET`/`$_POST`/`$_FILES`; notices přes transient
7. Všechny SQL přes `$wpdb->prepare()` a `%i`
8. Bonus: count_all, sort_order import, `@`, flush_rewrite_rules
9. Plugin Check + PHPCS; čistá instalace s `WP_DEBUG`
10. ZIP na „Add your plugin“, **krátká** odpověď na e-mail

---

## Testovací plán (WP_DEBUG = true, čistá instalace)

- [ ] Aktivace / deaktivace bez PHP notice/warning/deprecated
- [ ] Menu: WPA9 Gallery **pod** Comments, ne vedle Posts/Pages
- [ ] V `uploads/wpa9-galleries/` je `index.html`, **ne** `index.php` s PHP
- [ ] CRUD galerie a alba, upload více souborů, reorder, meta, bulk delete, regen thumbs
- [ ] Settings uložení včetně prázdných rozměrů
- [ ] Export JSON download; import dropzóna bez inline `<script>` (DevTools → žádný `<script>` v `.wpa9-wrap`)
- [ ] NextGEN import (pokud jsou tabulky) bez SQL chyb
- [ ] Shortcody `[wpa9_gallery]` masonry i native, `[wpa9_album]` + drill-down
- [ ] Native šablona: žádný `<style>` v HTML, styly v `<head>` u `wpa9-gallery-css`
- [ ] Multisite: `wp_upload_dir()` ukáže správný blogs.dir / sites/N
- [ ] Plugin Check: 0 error u sanitization, enqueue, prepared SQL, filesystem PHP

---

## Návrh odpovědi recenzentům

Po nahrání ZIP. Krátce, bez výpisu commitů (to recenze výslovně nechce):

> Thank you for the review. I have addressed the reported issues and re-checked the rest of the plugin for the same patterns (enqueue, sanitization, prepared SQL, uploads index file, menu position). I tested on a clean install with WP_DEBUG enabled.
>
> Ownership: WordPress.org username is aloziak; Contributors in readme is now aloziak; profile email is on apollo1.cz.
>
> Gallery files stay in uploads/wpa9-galleries/ (not in the plugin folder). Directory listing is prevented with an empty index.html instead of a PHP index file.
>
> Please review the latest upload.

---

## Mapování souborů k úpravám

| Soubor | Úpravy |
|--------|--------|
| `wpa9-gallery.php` | odstranit `load_plugin_textdomain()` |
| `readme.txt`, `README.md` | Contributors `aloziak`; sjednotit popis alb |
| `uninstall.php` | `DROP TABLE` přes `prepare` + `%i` |
| `includes/class-wpa9-admin.php` | menu 58; sanitize POST; notices transient; enqueue export JS |
| `includes/class-wpa9-ajax.php` | sanitize meta; `wp_check_filetype_and_ext`; ideálně `wp_handle_upload` |
| `includes/class-wpa9-install.php` | `index.html`; SQL migrace `prepare`; bez `flush_rewrite_rules` |
| `includes/class-wpa9-storage.php` | `index.html` místo PHP |
| `includes/class-wpa9-gallery.php` | SQL `%i` |
| `includes/class-wpa9-album.php` | SQL `%i`; volitelně `sort_order` u `add_gallery` |
| `includes/class-wpa9-image.php` | SQL `%i`; `count_all()` |
| `includes/class-wpa9-exporter.php` | SQL `%i` |
| `includes/class-wpa9-importer-nextgen.php` | SQL `%i` + `IN` placeholders |
| `includes/class-wpa9-importer.php` | předávat sort_order správně |
| `includes/class-wpa9-shortcode.php` | `wp_add_inline_style` pro native |
| `admin/views/export-import.php` | bez `<script>`; sanitize GET; `count_all()` |
| `admin/views/gallery-edit.php` | bez `onclick` |
| `admin/views/galleries-list.php` | bez `onsubmit` |
| `admin/views/albums-list.php` | bez `onsubmit` |
| `admin/views/import.php` | bez `onsubmit`; sanitize `$_GET['imported']` |
| `admin/js/admin.js` | confirm + select-on-focus |
| `admin/js/export-import.js` | **nový** soubor — dropzóna |
| `admin/css/admin.css` | stavy dropzóny |
| `templates/gallery-native.php` | bez `<style>` |
| `templates/gallery-masonry.php` | volitelně i18n span |

Nové soubory: `admin/js/export-import.js`; volitelně `assets/index.html` jako zdroj ke kopírování místo `file_put_contents`.
