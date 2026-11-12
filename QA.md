# Palautuksen tietosuojatarkistuksen testaus

Nykyinen lisäosa: `assignsubmission_datacheck` 1.0.2, vakaa julkaisu (stable, `2026111204`). Viimeisin dokumentoitu testiajo: 11.11.2026.

Version 1.0.2 julkaisutiedot vastaavat tiedoston `version.php` merkintöjä. Alla olevat testitulokset koskevat aiempia versioita; versiolle 1.0.2 ei ole tässä dokumentissa vahvistettua testiajoa.

## Version 1.0.2 tarkistuskorjaukset (12.11.2026)

Käyttäjän toimittamassa version 1.0.1 Moodle CI -ajotulosteessa PHP Lint, PHPMD, CodeSniffer ja Gruntin gherkinlint läpäisivät. PHPDoc Checker löysi puuttuvat parametrikuvaukset kolmesta Privacy API -metodista ja `submission_is_empty`-metodista. Validate löysi puuttuvan `@assignsubmission`-tagin Behat-tiedostosta. Savepoints- ja Mustache-tarkistukset ohitettiin, koska tarkistettavia tiedostoja ei ole.

Versio 1.0.2 täydentää näiden neljän metodin parametrien ja paluuarvojen kuvaukset sekä lisää vaaditun Behat-tagin. Toiminnallista koodia tai tietokantarakennetta ei muutettu. Moodle CI:n PHPDoc Checker ja Validate tulee ajaa uudelleen korjatulle versiolle; niitä ei ole vahvistettu paikallisessa ympäristössä.

Korjauksen jälkeen Moodle CodeSniffer (moodle-cs 3.9.0, PHP_CodeSniffer 4.0.4) läpäisi Moodle 4.5:n ja 5.3:n sääntöasetuksilla ilman virheitä tai varoituksia. PHP 8.3.36:n syntaksitarkistus hyväksyi kaikki yhdeksän PHP-tiedostoa.

## Version 1.0.1 staattiset tarkistukset (12.11.2026)

Moodle CodeSniffer (moodle-cs 3.9.0, PHP_CodeSniffer 4.0.4) tarkisti kaikki yhdeksän PHP-tiedostoa sekä Moodle 4.5:n että 5.3:n sääntöasetuksilla: ei virheitä eikä varoituksia. PHP 8.3.36:n syntaksitarkistus (`php -l`) hyväksyi kaikki yhdeksän tiedostoa.

PHPMD 2.15.0 tarkisti lisäosan Moodle Plugin CI:n virallisella `res/config/phpmd.xml`-säännöstöllä: ei rikkomuksia, paluuarvo 0.

Korjaukset koskevat Moodle-lisenssiotsakkeita, PHPDoc-kommentteja, testien kattavuusmerkintöjä, kielitekstien järjestystä ja koodin muotoilua. PHPMD-poikkeukset rajataan Moodlen vaatimiin käyttämättömiin parametreihin, rajapinnan määräämään `get_form_elements`-nimeen ja testiluokan metodimäärään. CodeSnifferin PHPDoc-tagitarkistuksesta poiketaan vain näiden PHPMD-merkintöjen kohdalla. Pelkän luokan määrittelevistä tuotantotiedostoista poistettiin tarpeettomat `MOODLE_INTERNAL`-tarkistukset.

Staattiset tarkistukset eivät korvaa PHPUnit- tai Behat-ajoja. Niitä ei ajettu uudelleen näiden korjausten yhteydessä.

## Version 0.1.5-beta testauksen tila

Käyttäjän toimittaman ajotulosteen mukaan PHPUnit-sarja ajettiin Moodle 5.3+:ssa (Build: 20261030), PHP 8.3.31:llä, MariaDB 11.4.13:lla ja PHPUnit 11.5.56:lla. Tulos: 19 testiä, 85 tarkistusta, yksi epäonnistuminen (18 testiä läpäisi).

Testi `test_visibility_requires_real_submission_content` odotti opettajan ohjetta HTML-merkinnät tekstiksi muutettuina. Version 0.1.5-beta tarkoitettu toiminta on näyttää ohje HTML-muotoiltuna. Testi päivitettiin tarkistamaan ohjeen sisältö, lihavointi ja rivinvaihto. Myös opiskelijan testi päivitettiin tarkistamaan, ettei ohje näy HTML-muotoiltunakaan. Tässä korjauksessa ei muutettu lisäosan toiminnallista koodia.

**Korjauksen jälkeinen PHPUnit-uusinta odottaa ajoa.** Versiolle 0.1.5-beta ei ole tässä dokumentissa vahvistettua hyväksyttyä PHPUnit- tai Behat-ajoa. Alla olevat hyväksytyt tulokset ja `artifacts/`-ajolokit koskevat aiempaa versiota.

## Aiemman version 0.1.3 testitulokset

Testattava lisäosa oli `assignsubmission_datacheck` 0.1.3-alpha. Testipäivä: 10.11.2026. Näiden testien jälkeen julkaisutiedoiksi päivitettiin MATURITY_BETA ja 0.1.3-beta. Toiminnallista koodia ei muutettu.

Testaus noudattaa Moodlen [QA-ohjeen](https://moodledev.io/general/development/process/testing/qa) käyttäjän näkökulmaa. Toimintojen ja tietojen käsittelyä tarkistetaan lisäksi [PHPUnitilla](https://moodledev.io/general/development/tools/phpunit) ja käyttöliittymää [Behatilla](https://moodledev.io/general/development/tools/behat).

## Eristys ja ympäristöt

PHPUnit-ajot käyttävät omia testitauluja ja datakansioita. Moodle 5.3+:n taulujen etuliite on `t_`, Moodle 4.5:n `dc45_`. Behat käyttää `b_`-tauluja ja erillistä `http://webserver`-testiosoitetta. Käyttöliittymätestit luovat omat opiskelijan, opettajan, kurssin ja tehtävät. Testeissä ei käytetä käyttäjän olemassa olevia tehtäväpalautuksia.

| Ympäristö | Testit | Tulos |
| --- | --- | --- |
| Moodle 4.5.15+ (Build: 20261030), PHP 8.3.31, MariaDB 11.4.13, PHPUnit 9.6.34 | 19 testiä, 88 tarkistusta | Hyväksytty |
| Moodle 5.3+ (Build: 20261030), PHP 8.3.31, MariaDB 11.4.13, PHPUnit 11.5.56 | 19 testiä, 88 tarkistusta | Hyväksytty |
| Moodle 5.3+, Boost, Selenium-Firefox, Behat | 7 skenaariota, 82 askelta | Hyväksytty |

Ajolokit ovat projektin `artifacts/phpunit-moodle45.txt`- ja `artifacts/phpunit-moodle53.txt`-tiedostoissa. Behat-ajon loki on `artifacts/behat-moodle53.txt`.

## PHPUnit-testien kattamat asiat

`tests/submission_test.php`:

1. Kaikki kolme vastausta tallentuvat. Muokkaaminen päivittää samaa tietuetta.
2. Puuttuva tai virheellinen vastaus hylätään; virheilmoitus on luettava.
3. Pelkkä ilmoitus ei kelpaa tehtäväsisällöksi. Oikea tiedosto tuo vastauksen näkyviin; tiedoston poisto palauttaa tilanteen tyhjäksi.
4. Lomake sisältää kysymyksen ja palauttaa tallennetun valinnan muokkausta varten.
5. Virheellinen muokkaus ei korvaa aiempaa vastausta.
6. Lopullisen palautuksen esitarkistus hylkää myös tietokannassa olevan virheellisen vastauksen.
7. Opiskelija näkee kysymyksen ja vastauksen mutta ei opettajan ohjetta.
8. Verkkoteksti mahdollistaa yhteenvedon näyttämisen. Käytöstä poistettu sisältölisäosa ei tee ilmoituksesta tehtäväsisältöä.
9. Palautuksen kopiointi säilyttää vastauksen; kopion poistaminen ei poista alkuperäistä.
10. Tehtävän poisto poistaa vain kyseisen tehtävän ilmoitukset.
11. Tehtävän asetusten ohjetekstit löytyvät.
12. Ryhmän jäsenet muokkaavat yhtä yhteistä palautuskohtaista ilmoitusta.

`tests/privacy/provider_test.php`:

13. Metatiedoissa ilmoitetaan vastaukset sisältävä tietokantataulu.
14. Opiskelijan vienti sisältää oikean vastauksen ja muokkausajan.
15. Arvioijan tietojen vientiin ei lisätä opiskelijan ilmoitusta.
16. Käyttäjäkohtainen poisto säilyttää muiden opiskelijoiden ja muiden tehtävien tiedot.
17. Kontekstikohtainen poisto säilyttää muiden tehtävien tiedot.
18. Massapoisto koskee vain pyydettyjä käyttäjiä kyseisessä tehtävässä.
19. Tyhjä massapoistopyyntö ei poista mitään.

## Selaimessa suoritettavat hyväksymistestit

Automaattinen toteutus on tiedostossa `tests/behat/declaration.feature`. Selain on Selenium-Firefox ja teema Boost. Jokainen skenaario aloitetaan omasta tyhjästä testiaineistostaan.

| Tapaus | Toimenpiteet | Hyväksymisehto |
| --- | --- | --- |
| QA-01, kolme vaihtoehtoa | Opiskelija lisää verkkotekstin, valitsee vuorollaan Yes / No / Not sure ja tallentaa. | Jokainen vaihtoehto sallii palautuksen; otsikko ja nimetty vastaus näkyvät. Opettajan ohje ei näy opiskelijalle. |
| QA-02, pakollisuus | Opiskelija lisää verkkotekstin mutta jättää vastauksen valitsematta ja tallentaa. | Puuttuvan vastauksen virheilmoitus näkyy; palautus ei siirry arvioitavaksi. |
| QA-03, muokkaaminen | Opiskelija tallentaa Not sure -vastauksen, avaa muokkauksen ja vaihtaa vastauksen No-valinnaksi. | Aiempi valinta on muistissa; yhteenveto näyttää uuden vastauksen. |
| QA-04, arviointipaneeli | Opiskelija palauttaa työn. Opettaja avaa opiskelijan varsinaisen AJAX-arviointipaneelin. | Vastaus ja opettajan tulkintaohje näkyvät ilman lataus- tai JSON-virhettä. |
| QA-05, lisäosa pois käytöstä | Opiskelija avaa tehtävän, jossa tietosuojatarkistus on poistettu käytöstä. | Kysymystä ei näytetä. |

QA-01 suoritetaan kolmena skenaariona, joten selaintestisarjassa on yhteensä seitsemän skenaariota. Ensimmäisessä ajossa kuusi läpäisi ja yksi pysähtyi Moodlen JavaScript-latauksen aikarajaan. Aikarajaan pysähtynyt skenaario läpäisi uusinnan pidemmällä aikarajalla. Lopullisessa koko sarjan ajossa kaikki seitsemän skenaariota ja 82 askelta läpäisivät. Ajo kesti 2 minuuttia 1 sekunnin. Docker-ympäristön `MOODLE_DOCKER_TIMEOUT_FACTOR` oli lopullisessa ajossa 3.

## Vielä tehtävät tarkistukset

- Moodle 4.5:n selaintestit: tässä ympäristössä 4.5:n testit on ajettu PHPUnitilla, ei selaimella.
- Versioiden 5.0, 5.1 ja 5.2 ajotestit sekä muut tuetut PHP-versiot ja tietokannat.
- Suomenkielisen käyttöliittymän silmämääräinen tarkistus, näppäimistökäyttö ja ruudunlukija.
- Tiedostojen lataus selaimessa, lopullinen lähetyspainike ja uusi palautusyritys kokonaisina käyttäjäpolkuina.
- Koko Moodlen tietopyyntöprosessin vienti ja poisto sekä ryhmäpalautusten tietosuojapyynnöt.
- Mobiilisovellus ja ulkoiset verkkopalvelurajapinnat.
- Varmuuskopiointi ja palautus: lisäosa ei toteuta tätä tukea. Testien läpäisy ei poista tätä rajoitusta.

## Testien ajaminen uudelleen

Asenna lisäosa testattavaan Moodle-lähdekoodiin ja alusta Moodlen omat testiympäristöt. Käytä erillisiä testitauluja ja testidatakansioita; älä määritä niitä samoiksi kuin käytössä olevan sivuston.

Moodle 4.5:n juurihakemistossa:

```sh
vendor/bin/phpunit --test-suffix _test.php mod/assign/submission/datacheck/tests
```

Moodle 5.3:n juurihakemistossa:

```sh
vendor/bin/phpunit --test-suffix _test.php public/mod/assign/submission/datacheck/tests
vendor/bin/behat --config /polku/behatdata/behatrun/behat/behat.yml --tags=@assignsubmission_datacheck --format=progress
```
