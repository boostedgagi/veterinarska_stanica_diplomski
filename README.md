# 🐾 Veterinary Clinic — Backend (Diplomski rad)

Ovo je backend deo diplomskog rada koji sam izradio za potrebe završnog projekta na Visokoj tehničkoj školi u Subotici.
Projekat predstavlja sistem za veterinarsku kliniku, koji omogućava zakazivanje pregleda, vođenje zdravstvenih kartona, komunikaciju sa veterinarima i praćenje istorije tretmana ljubimaca.

Frontend deo aplikacije razvijen je zasebno u Angular framework-u, dok se ovaj repozitorijum odnosi isključivo na backend implementiran u Symfony 8.2.

# ⚙️ Tehnologije i komponente

Projekt je izgrađen korišćenjem sledećih tehnologija i Symfony komponenti:

🧩 Symfony 8.2 — PHP framework

💾 Doctrine ORM — rad sa MySQL bazom podataka

✉️ Mailer — sistem za slanje notifikacionih i sistemskih mejlova

💬 Messenger — asinkrono procesiranje poruka i zadataka

🔐 Security Component — autentikacija i autorizacija korisnika

🧠 Event Subscribers & Listeners — reagovanje na događaje u aplikaciji

🧰 Dependency Injection Container — za bolje upravljanje servisima

🕒 Cron Jobs / Command Scheduler — automatsko izvršavanje periodičnih zadataka

🧾 Twig + Templated Emails — generisanje vizuelno prilagođenih mejlova

🧠 Custom Factories & Services — za modularnost i čistu arhitekturu

🐳 Docker - Kontejnerizacija aplikacije 

# 📡 Ključne funkcionalnosti

✅ Registracija i autentikacija korisnika

🐕‍🦺 Kreiranje, pregled i ažuriranje zdravstvenih kartona ljubimaca

📅 Zakazivanje i upravljanje veterinarskim pregledima

👨‍⚕️ Evidencija veterinara i praćenje njihove popularnosti

📈 Automatsko ažuriranje statistika putem event sistema

📨 Slanje potvrda i obaveštenja putem mejla

🔄 Periodični zadaci putem cron komandi

# 📖 API dokumentacija

Swagger UI (NelmioApiDocBundle) je dostupan na `GET /doc`, a OpenAPI specifikacija u JSON formatu na `GET /doc.json`.
Rute označene katancem zahtevaju JWT: token se dobija preko `POST /login_check` i šalje kao `Authorization: Bearer <token>`.

# ⏰ Zakazani poslovi

Ponavljajući poslovi se pokreću iz same aplikacije (Symfony Scheduler, `#[AsCronTask]` na komandama), dovoljan je jedan worker:

```bash
php bin/console messenger:consume scheduler_default   # ili: make scheduler
php bin/console debug:scheduler                       # pregled rasporeda
```

| Kada | Posao |
|---|---|
| svakog sata | podsetnik za termine u narednih 24 h (`NotifyExaminationByEmail --time-range=today`) |
| svakog dana u 08:00 | podsetnik za termine u narednih 7 dana (`--time-range=next_week`) |
| 1. u mesecu u 06:00 | CSV izveštaj poslednjih 30 dana administratoru (`MonthlyHealthRecordExport`) |
| svake noći u 03:30 | brisanje isteklih tokena (`delete-expired-tokens`) |

# 💡 Napomena

Ovaj repozitorijum predstavlja demonstraciju backend arhitekture i logike korišćene u okviru diplomskog rada.
Frontend deo aplikacije, implementiran u Angularu, nalazi se u posebnom repozitorijumu.

# 👤 Autor

Dragan Jelić

📧 boostedgagi@boostedgagi.com

🌐 vetshop.boostedgagi.com

# 🏫 Projekat izrađen u okviru višeg obrazovanja

Diplomski rad izrađen kao završni projekat na Visokoj školi, u okviru studijskog programa Računarstvo i informatika.
Tema: „Izrada informacionog veb sistema za potrebe veterinarske klinike“
