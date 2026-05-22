# General

Create a php-mysql-cron based system for registering to a music club, send weekly reminders to the participants to confirm/reject their participation.

I will test locally - instead of sending email, send the text to a log file in the testing phase.


# Database and Tables

Database: v2404_registration
User: v2404_reg
Password: PaulHindemith

Tables:

describe registrations;
+------------+------------------+------+-----+---------------------+----------------+
| Field      | Type             | Null | Key | Default             | Extra          |
+------------+------------------+------+-----+---------------------+----------------+
| id         | int(10) unsigned | NO   | PRI | NULL                | auto_increment |
| created_at | timestamp        | YES  |     | current_timestamp() |                |
| name       | varchar(100)     | NO   |     | NULL                |                |
| email      | varchar(150)     | NO   |     | NULL                |                |
| instrument | varchar(100)     | YES  |     | NULL                |                |
| experience | text             | YES  |     | NULL                |                |
| comments   | text             | YES  |     | NULL                |                |
+------------+------------------+------+-----+---------------------+----------------+

describe ensembles;
+----------+------------------+------+-----+---------+----------------+
| Field    | Type             | Null | Key | Default | Extra          |
+----------+------------------+------+-----+---------+----------------+
| id       | int(10) unsigned | NO   | PRI | NULL    | auto_increment |
| name     | varchar(100)     | NO   |     | NULL    |                |
| name_est | varchar(100)     | NO   |     | NULL    |                |



describe registration_ensembles;
+-----------------+------------------+------+-----+---------+-------+
| Field           | Type             | Null | Key | Default | Extra |
+-----------------+------------------+------+-----+---------+-------+
| registration_id | int(10) unsigned | NO   | PRI | NULL    |       |
| ensemble_id     | int(10) unsigned | NO   | PRI | NULL    |       |
+-----------------+------------------+------+-----+---------+-------+

describe attendance;
+-----------------+------------------+------+-----+---------+----------------+
| Field           | Type             | Null | Key | Default | Extra          |
+-----------------+------------------+------+-----+---------+----------------+
| id              | int(10) unsigned | NO   | PRI | NULL    | auto_increment |
| ensemble_id     | int(10) unsigned | NO   | MUL | NULL    |                |
| registration_id | int(10) unsigned | NO   | MUL | NULL    |                |
| date            | date             | NO   |     | NULL    |                |
| attendance      | enum('yes','no') | YES  |     | NULL    |                |
+-----------------+------------------+------+-----+---------+----------------+

# Create registration page

Support language choice Estonian/English

On the page (Estonian text, translate for English page):
-----

Koosmänguklubi - registreerumine

KOOSMÄNGUKLUBI on võimalus mängida üheskoos. See on mõeldud eelkõige täiskasvanutest amatöörmuusikutele, kuid teretulnud on kõik, kel huvi. 

Kahjuks ei ole toimumiskohas klaverit ja osaleda saab ainult nende pillidega, mida osaleja saab ise kohale tuua.

Koosmängud toimuvad laupäeviti/pühapäeviti Guitarium ruumides, Planeedi 5, Tallinn.
Praeguse seisuga:
P 11.00-12.00 Ladusalt noodist lugejad (lõpetanud muusikakooli, keskastme, kõrgkooli vms. Lood ei ole iseenesest rasked)
L 17.00-18.00 Improvisatsiooniansambel (sobib kõikidele, tase pole määrav)
Kui liitub veel huvilisi  Lihtsate lugude ansambel (õppinud mõned aastad või omal käel). Aeg L 18.30 või P 12.30.

Koosmäng toimub, kui noodist mängimise ansamblis on vähemalt 3 osalist ning improvisatsiooniansamblis vähemalt 2 osalist. Eelneval õhtul saadan kinnituse.

Peale registreerumist saate lingi tabelile, kus märkida oma osavõtt.

Kui on küsimusi, kirjutage julgelt: koosmanguklubi@gmail.com 

Aitäh!

Tarmo

[Form:]
Nimi:
Email:
Instrument/Hääl:
Sooviksin osaleda (võite valida mitu):
- Klassikalise muusika ansambel (ladusalt noodist lugejad)
- Improvisatsiooniansambel
- Klassikalise muusika ansamble (algajad)
Kirjeldage oma senist muusikalist kogemust/tausta:
Mida soovite lisada?

[Submit]

------

Enter the data to tables.

After registration send an confirmation email:

---
Aitäh Registreerimast! Edasisi osalemisi saate sisse kanda sellel lehel: <URL> ;
Igal nädalal saadetakse ka meeldetuletav e-mail.

---

In the URL add also users email as parameter to log in passwordlessly.


# Create attendance page

[Title: ] Koosmänguklubi -  osalemine

[Login: ] -  require registered email to use the page. No password needed. Support also logging in by URL sent after registration.

[Intro: ] Palun märkige + kui saate tulla, - kui mitte ning jätke tühjaks kui veel ei tea;

---

Below create tables by ensembles- if there are any participant for it. 

Set names participating in that ensemble as rows, dates as columns.
For ensemble 1 (Klassikalise muusika ensemble) set Sundays until 14.06.26, for Impro ensemble Saturdays until 13.06.

Prefill the table from database and store changes.


# Create reminders mechanism

On Fridays at 3 PM send a reminding email to participants in Estonian and English, IF the user hase not eneterd its attendance to the coming Saturday/Sunday. The message should take into account which ensemble the person is attending and the coming date of that ensemble.

----
Meeldetuletus: palun kinnitage, kas osalete Koosmänguklubi ansamblis <ensemble> <date>-l:

JAH | EI

-----

# Create unsubscibe page

When user unsubscibes, remove it from database and clear its attendances.

Show response:

"Kahju, et lahkute. Olete alati teretulnud uuesti liituma lehel <registration URL>".







