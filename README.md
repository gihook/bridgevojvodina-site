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

