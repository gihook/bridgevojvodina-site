# Bridge Vojvodina

Ovo je repozitorijum za veb-sajt [bridgevojvodina.rs](https://bridgevojvodina.rs).

## Lokalno pokretanje

Projekat se može pokrenuti lokalno koristeći Docker Compose.

### Preduslovi

- Instaliran [Docker](https://docs.docker.com/get-docker/) i [Docker Compose](https://docs.docker.com/compose/install/).

### Uputstvo za pokretanje

1. Klonirajte repozitorijum:

   ```bash
   git clone https://github.com/nikola/bridgevojvodina-site.git
   cd bridgevojvodina-site
   ```

2. Konfigurišite `.env` fajlove za Docker i Laravel:
   - Kreirajte `bridgevojvodina-site/.env` fajl na osnovu podešavanja baze u `docker-compose.yml`.
   - Iskopirajte `bridgevojvodina-laravel/.env.example` u `bridgevojvodina-laravel/.env` i postavite pristup bazi u skladu sa Docker konfiguracijom.

3. Pokrenite kontejnere:
   ```bash
   docker-compose up -d
   ```

4. Pokrenite skriptu za inicijalno podešavanje (instalira Composer pakete, instalira NPM pakete, bilda asset-e, generiše ključ i puni bazu):
   ```bash
   ./setup.sh
   ```

### Frontend razvoj (NPM)

Za razvoj frontenda možete koristiti privremeni Node kontejner kako biste izbegli instalaciju Node-a na lokalnoj mašini:

- **Instalacija paketa:**
  ```bash
  docker run --rm -v $(pwd)/bridgevojvodina-laravel:/app -w /app node:20 npm install
  ```
- **Build asset-a:**
  ```bash
  docker run --rm -v $(pwd)/bridgevojvodina-laravel:/app -w /app node:20 npm run build
  ```
- **Watch mod (za razvoj):**
  ```bash
  docker run --rm -it -v $(pwd)/bridgevojvodina-laravel:/app -w /app -p 5173:5173 node:20 npm run dev
  ```

Nakon pokretanja, servisi su dostupni na sledećim adresama:

- **Sajt:** [http://localhost:8082](http://localhost:8082)
- **phpMyAdmin:** [http://localhost:8081](http://localhost:8081)

## Formati za uvoz podataka

### 1. Podela karata (PBN)
Za uvoz podela karata koristi se PBN (*Portable Bridge Notation*) format.
Fajl treba da sadrži sledeće tagove za svaki bord:
- `[Board "N"]` - Broj borda.
- `[Deal "D:N_ruka E_ruka S_ruka W_ruka"]` - Raspored karata. Karte su grupisane po bojama (Pik.Herc.Karo.Tref) i odvojene tačkom. Ruke su odvojene razmakom. Početno slovo (npr. `N:`) označava delitelja.
  - Primer: `[Deal "N:AKQJ.AKQJ.AKQJ.AK 2.2.2.2 3.3.3.3 4.4.4.4"]`
- `[Event "Naziv"]` - Opciono, postavlja naziv seta bordova.

### 2. Kola i mečevi (CSV)
Za masovni uvoz kola i mečeva u okviru turnira koristi se CSV fajl. Prvi red (zaglavlje) se preskače.
**Kolone:**
1. `Kolo` - Naziv kola (npr. "Kolo 1").
2. `Domaćin` - Broj tima domaćina (mora odgovarati brojevima timova definisanim na turniru).
3. `Gost` - Broj tima gosta.
   - Ako je jedan od timova "bye", meč se tretira kao slobodno kolo.

### 3. Rezultati meča (CSV)
Za uvoz rezultata po bordovima unutar pojedinačnog meča (otvorena ili zatvorena soba) koristi se CSV fajl.
- **Zaglavlje:** `bd,contract,by,lead,result`
- **Kolone:**
  1. `bd` - Broj borda (ceo broj).
  2. `contract` - Kontrakt (npr. `4S`, `3NT`, `4SX`, `Pass`). Podržani su `X` za kontru i `XX` za rekontru.
  3. `by` - Izvođač (`N`, `E`, `S`, `W`).
  4. `lead` - Ataka (npr. `SA`, `H10`).
  5. `result` - Rezultat u odnosu na kontrakt (npr. `=`, `+1`, `-2`).

Sistem automatski izračunava poene i IMP-ove na osnovu unetih podataka i ranjivosti borda.


