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

2. Pokrenite kontejnere:
   ```bash
   docker compose up -d
   ```

Nakon pokretanja, servisi su dostupni na sledećim adresama:

- **Sajt:** [http://localhost:8080](http://localhost:8080)
- **phpMyAdmin:** [http://localhost:8081](http://localhost:8081)

