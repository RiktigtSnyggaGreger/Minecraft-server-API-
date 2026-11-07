# Minecraft Stats API

Ett JSON-API som visar statistik från en Minecraft-server: spelare, stats, advancements, topplistor och vem som är online just nu.

Byggt med PHP 8.4, nginx och MariaDB i Docker, och publicerat via en Cloudflare-tunnel.

## Innehåll

- [Kom igång](#kom-igång)
- [Routes](#routes)
  - [Status](#status)
  - [Spelare](#spelare)
  - [Topplistor](#topplistor)
- [Enheter](#enheter)
- [Fel](#fel)
- [Projektstruktur](#projektstruktur)
- [Att tänka på](#att-tänka-på)

---

## Kom igång

### Krav
- Docker med Docker Compose
- En Minecraft-server vars datamapp finns på `/home/ed/minecraft/data` och som lyssnar på port `25565`

### `.env`
Skapa en `.env` i projektets rot (den ska **aldrig** in i git):

```env
TUNNEL_TOKEN=...        # från Cloudflare Zero Trust
DB_ROOT_PASSWORD=...
DB_PASSWORD=...
```

### Starta
```bash
docker compose up -d --build
```

### Bra kommandon
```bash
docker compose logs -f php                         # PHP-fel hamnar här (de visas inte publikt)
docker compose exec php php -l /var/www/html/x.php # syntaxkolla en fil
docker compose up -d --build php                   # bygg om PHP efter ändringar i php/Dockerfile
```

---

## Routes

Alla routes är `GET` och svarar med JSON. `Access-Control-Allow-Origin: *` är satt, så API:et kan anropas från en frontend på vilken domän som helst.

| Route | Beskrivning |
|---|---|
| [`/`](#get-) | Hälsokontroll |
| [`/server`](#get-server) | Minecraft-serverns status och vilka som är online |
| [`/stats`](#get-stats) | Totaler för hela servern |
| [`/players`](#get-players) | Alla spelare med sammanfattning |
| [`/players/{id}`](#get-playersid) | En spelare med alla stats |
| [`/players/{id}/advancements`](#get-playersidadvancements) | En spelares advancements |
| [`/leaderboard`](#get-leaderboard) | Topp 3 i alla topplistor |
| [`/leaderboard/{typ}`](#get-leaderboardtyp) | En hel topplista |
| [`/leaderboard/{kategori}/{sak}`](#get-leaderboardkategorisak) | Topplista för en specifik sak, t.ex. ett block eller en mob |

`{id}` kan vara **antingen** ett spelarnamn eller ett UUID. Namn, typer och kategorier är skiftlägesokänsliga.

---

### Status

#### `GET /`
Hälsokontroll. Svarar bara `200` om databasen **och** Minecraft-datan går att nå.

```json
{ "status": "ok", "database": "up", "minecraft_data": "up" }
```

| Status | Betyder |
|---|---|
| `200` | Allt fungerar |
| `500` | Databasen går inte att nå |
| `503` | Minecraft-datan går inte att läsa |

#### `GET /server`
Pratar direkt med Minecraft-servern (samma sak som serverlistan i spelet gör).

```json
{
  "online": true,
  "version": "Paper 26.3",
  "motd": "Edwins fina minecraft server",
  "players_online": 1,
  "players_max": 20,
  "players": ["Vattenpannkaka"]
}
```

Om servern är avstängd:
```json
{ "online": false, "error": "Connection refused" }
```
Det är fortfarande status `200`, eftersom "servern är offline" är ett giltigt svar och inget fel i API:et.

#### `GET /stats`
Totaler för alla spelare tillsammans.

```json
{
  "players": 5,
  "play_time": 15.9,
  "deaths": 2,
  "mob_kills": 268,
  "damage_dealt": 4252,
  "walked_blocks": 14006,
  "blocks_mined": 5915,
  "items_crafted": 4065
}
```

---

### Spelare

#### `GET /players`
Alla som har spelat, sorterade efter speltid (mest först).

```json
[
  {
    "uuid": "328f55df-4e2d-42dc-9f8f-7d36a3433849",
    "name": "SaintJamesBaddie",
    "play_time_hours": 8.6,
    "last_seen": "2026-11-07T16:17:16+00:00",
    "advancements": 26,
    "advancements_total": 126
  }
]
```

| Fält | Beskrivning |
|---|---|
| `name` | Kan vara `null` om namnet saknas i Minecrafts usercache |
| `last_seen` | När servern senast sparade spelarens data. Ungefärligt, inom ca 5 minuter. ISO 8601, UTC |
| `advancements_total` | Antal advancements i spelet (utan recept) |

#### `GET /players/{id}`
En spelare med alla stats. `{id}` = namn eller UUID.

```json
{
  "uuid": "328f55df-4e2d-42dc-9f8f-7d36a3433849",
  "name": "SaintJamesBaddie",
  "play_time_hours": 8.6,
  "last_seen": "2026-11-07T16:17:16+00:00",
  "stats": {
    "mined":   { "stone": 1200, "deepslate_diamond_ore": 2 },
    "killed":  { "creeper": 13, "zombie": 40 },
    "custom":  { "jump": 15859, "walk_one_cm": 1104100 }
  }
}
```

`stats` innehåller **rådata** från Minecraft, med `minecraft:` borttaget. Kategorier: `mined`, `crafted`, `used`, `broken`, `picked_up`, `dropped`, `killed`, `killed_by`, `custom`. Se [Enheter](#enheter) för vad talen i `custom` betyder.

Svarar `404` om spelaren inte finns.

#### `GET /players/{id}/advancements`
Klara advancements (utan recept) och när de klarades.

```json
{
  "uuid": "328f55df-4e2d-42dc-9f8f-7d36a3433849",
  "name": "SaintJamesBaddie",
  "count": 26,
  "total": 126,
  "advancements": {
    "story/mine_diamond": "2026-10-19 17:51:33 +0000",
    "adventure/kill_a_mob": "2026-10-19 18:04:46 +0000"
  }
}
```

Datumen är i Minecrafts eget format (UTC). Nycklar som slutar på `/root` är flikarna i advancement-menyn (Story, Nether osv.).

---

### Topplistor

Alla topplistor är sorterade med högst värde först och har samma format:

```json
[
  { "uuid": "9ee3338c-605c-4e18-bf8a-e155e877b69f", "name": "Vattenpannkaka", "value": 1 }
]
```

#### `GET /leaderboard`
Topp 3 i varje topplista.

```json
{
  "play_time": [ { "uuid": "...", "name": "SaintJamesBaddie", "value": 8.6 }, ... ],
  "deaths":    [ ... ],
  ...
}
```

#### `GET /leaderboard/{typ}`
En hel topplista.

| Typ | Värde | Enhet |
|---|---|---|
| `play_time` | Speltid | timmar |
| `longest_life` | Tid sedan senaste dödsfall (nuvarande liv, **inte** rekord) | timmar |
| `deaths` | Dödsfall | st |
| `mob_kills` | Dödade mobs | st |
| `damage_dealt` | Skada given | hjärtan |
| `walked_blocks` | Gått | block |
| `blocks_mined` | Block brutna, alla sorter | st |
| `items_crafted` | Saker craftade, alla sorter | st |

Okänd typ ger `404`.

#### `GET /leaderboard/{kategori}/{sak}`
Topplista för en specifik sak. Använd samma namn som i Minecraft, utan `minecraft:`.

```
/leaderboard/mined/deepslate_diamond_ore   vem har hittat flest diamanter (djupt ner)
/leaderboard/killed/creeper                vem har dödat flest creepers
/leaderboard/killed_by/zombie              vem har dödats mest av zombies
/leaderboard/crafted/torch                 vem har craftat flest facklor
```

Kategorier: `mined`, `crafted`, `used`, `broken`, `picked_up`, `dropped`, `killed`, `killed_by`.

- Okänd **kategori** ger `404`.
- Okänd **sak** ger en lista där alla har `0` (API:et kan inte veta om saken finns i spelet eller bara inte har använts än).

---

## Enheter

API:et räknar om de flesta värdena, men `stats` i `/players/{id}` är rådata:

| Minecraft-enhet | Används i | Omräkning |
|---|---|---|
| ticks (20 per sekund) | `play_time`, `time_since_death`, `sneak_time` m.fl. | ÷ 72 000 = timmar |
| cm | allt som slutar på `_one_cm` | ÷ 100 = block (1 block = 1 m) |
| tiondels hjärtan | `damage_dealt`, `damage_taken` | ÷ 10 = hjärtan |

Tider i API:et (`last_seen`) är i **ISO 8601, UTC**. Låt frontenden göra om till lokal tid och "för 2 timmar sedan".

---

## Fel

Fel svarar alltid med JSON:

```json
{ "error": "Player not found" }
```

| Status | När |
|---|---|
| `404` | Okänd route, spelare, topplista eller kategori |
| `500` | Databasen går inte att nå |
| `503` | Minecraft-datan går inte att läsa (bara `/`) |

---

## Projektstruktur

```
app/
├── index.php        Router: vilka URL:er som finns
├── helper.php       respond() och read_json()
├── db.php           Anslutning till MariaDB ($db)
├── player.php       Spelare, stats och advancements
├── leaderboard.php  Topplistor och /stats
└── server.php       /server, pratar Minecrafts status-protokoll
php/Dockerfile       PHP-image (mysqli, döljer fel och version)
nginx.conf           Skickar allt till index.php
docker-compose.yml   nginx, PHP, MariaDB och Cloudflare-tunnel
```

### Var datan kommer ifrån
Minecraft-mappen är monterad **read-only** på `/minecraft` i PHP-containern.

| Fil | Innehåller |
|---|---|
| `/minecraft/usercache.json` | UUID och namn |
| `/minecraft/world/players/stats/<uuid>.json` | Stats per spelare |
| `/minecraft/world/players/advancements/<uuid>.json` | Advancements per spelare |

Alla som har en stats-fil räknas som spelare. Usercache används bara för att slå upp namn.

---

## Att tänka på

- **När Minecraft uppdateras:** uppdatera `ADVANCEMENTS_TOTAL` i `app/player.php`. Antalet finns i serverns jar-fil under `data/minecraft/advancement/` (räkna JSON-filerna utom `recipes/`).
- **Ny topplista:** lägg till den på **två** ställen i `app/leaderboard.php`: i `LEADERBOARDS` och i `match` i `leaderboard_value()`.
- **API:et är read-only och publikt.** Det finns ingen inloggning, eftersom det inte går att ändra något. Lägger du till `POST`-routes eller privat data behövs inloggning.
- **Uppdateringar:** kör `docker compose pull && docker compose up -d --build` då och då för säkerhetsfixar.
