🐾 Veterinary Clinic — Backend (Diplomski rad)

Ovo je backend deo diplomskog rada koji sam izradio za potrebe završnog projekta na Visokoj školi.
Projekat predstavlja sistem za veterinarsku kliniku, koji omogućava zakazivanje pregleda, vođenje zdravstvenih kartona, komunikaciju sa veterinarima i praćenje istorije tretmana ljubimaca.

Frontend deo aplikacije razvijen je zasebno, dok se ovaj repozitorijum odnosi isključivo na backend implementiran u Symfony 6.4.

⚙️ Tehnologije i komponente

Projekt je izgrađen korišćenjem sledećih tehnologija i Symfony komponenti:

🧩 Symfony 6.4 — PHP framework

💾 Doctrine ORM — rad sa bazom podataka

✉️ Mailer — sistem za slanje notifikacionih i sistemskih mejlova

💬 Messenger — asinkrono procesiranje poruka i zadataka

🔐 Security Component — autentikacija i autorizacija korisnika

🧠 Event Subscribers & Listeners — reagovanje na događaje u aplikaciji

🧰 Dependency Injection Container — za bolje upravljanje servisima

🕒 Cron Jobs / Command Scheduler — automatsko izvršavanje periodičnih zadataka

🧾 Twig + Templated Emails — generisanje vizuelno prilagođenih mejlova

🧠 Custom Factories & Services — za modularnost i čistu arhitekturu

🗂️ Struktura projekta
api/
├── config/           # Konfiguracija Symfony aplikacije
├── src/
│   ├── Controller/   # API kontroleri
│   ├── Entity/       # Doctrine entiteti
│   ├── Event/        # Eventi i subscriberi
│   ├── Factory/      # Fabrike za poslovnu logiku
│   ├── Repository/   # Repozitorijumi za upite
│   └── Service/      # Servisi, mail i pomoćne klase
├── templates/        # Email šabloni
├── migrations/       # Doctrine migracije
└── tests/            # Unit testovi

📡 Ključne funkcionalnosti

✅ Registracija i autentikacija korisnika

🐕‍🦺 Kreiranje, pregled i ažuriranje zdravstvenih kartona ljubimaca

📅 Zakazivanje i upravljanje veterinarskim pregledima

👨‍⚕️ Evidencija veterinara i praćenje njihove popularnosti

📈 Automatsko ažuriranje statistika putem event sistema

📨 Slanje potvrda i obaveštenja putem mejla

🔄 Periodični zadaci putem cron komandi

💡 Napomena

Ovaj repozitorijum predstavlja demonstraciju backend arhitekture i logike korišćene u okviru diplomskog rada.
Frontend deo aplikacije, implementiran u Angularu, nalazi se u posebnom repozitorijumu.

👤 Autor

Dragan Jelić
📧 boostedgagi@boostedgagi.com
🌐 vetshop.boostedgagi.com

🏫 Projekat izrađen u okviru višeg obrazovanja

Diplomski rad izrađen kao završni projekat na Visokoj školi, u okviru studijskog programa Računarstvo i informatika.
Tema: „Izrada informacionog veb sistema za potrebe veterinarske klinike“
