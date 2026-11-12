# HAMK – Palautuksen tietosuojatarkistus

[English](README.en.md)

Moodle 4.5+:lle tarkoitettu lisäosa: `assignsubmission_datacheck`, vakaa versio 1.0.0 (stable).
PHPUnit-testit on ajettu Moodle 4.5.15+:ssa ja 5.3+:ssa. Selainajojen tulokset ja testauksen rajaukset ovat tiedostossa [QA.md](QA.md).

## Toiminta

Lisää pakollisen, kolme vastausvaihtoehtoa sisältävän kysymyksen **Muokkaa palautusta** -lomakkeelle tehtävissä, joissa lisäosa on käytössä. Vastaus tallennetaan `assignsubmission_datacheck`-tauluun ja yhdistetään Moodlen tehtävään sekä palautuksen tunnisteeseen (`assign_submission.id`). Tallennettu vastaus näytetään opiskelijalle ja arvioijalle palautuksen yhteenvedossa. Vastaukset **Kyllä** ja **En osaa sanoa** eivät estä palautuksen tallentamista: kysymys on ohjaava.

## Asennus

**Moodle 4.5 ja 5.0:** sijoita `datacheck`-kansio Moodlen juurihakemiston alle polkuun `mod/assign/submission/datacheck/`. Suorita `php admin/cli/upgrade.php` verkkopalvelun käyttäjänä tai avaa Moodlessa **Sivuston hallinta → Ilmoitukset**.

**Moodle 5.1 ja uudemmat:** käytä polkua `public/mod/assign/submission/datacheck/` ja komentoa `php public/admin/cli/upgrade.php`.

Ota palautustapa käyttöön sivuston hallinnan lisäosa-asetuksissa (**Lisäosat → Tehtävän lisäosat → Palautuslisäosat**) ja testitehtävän **Palautusmuodot**-asetuksissa. Pidä myös tiedostopalautus tai verkkoteksti käytössä varsinaista tehtäväsisältöä varten. Tietosuojavastaus näytetään tallennetun tehtäväsisällön rinnalla, eikä pelkkä vastaus tee palautuksesta sisällöllistä.

Jos päivität olemassa olevaa asennusta, korvaa lisäosan tiedostot ja suorita päivitys uudelleen. Versio 0.1.2 ei edellytä tietokantarakenteen muutoksia. Jos uudet kielitekstit eivät näy, tyhjennä Moodlen välimuistit kohdasta **Sivuston hallinta → Kehitys → Tyhjennä kaikki välimuistit**.

Versio 0.1.2 lisää tehtävän asetusten ohjekuvakkeen tarvitsemat kielitekstit (`enabled` ja `enabled_help`) suomeksi ja englanniksi. Tämä korjaa ilmoituksen `Help title string does not exist: [enabled, assignsubmission_datacheck]`.

Versio 0.1.5-beta näyttää opettajan ohjeen HTML-muotoilut, kuten lihavoinnin ja rivinvaihdot. Muutos ei edellytä tietokantarakenteen muutoksia.

## Tallennettavat tiedot

Versiossa 0.1.3 palautuksen yhteenvedossa näytetään tietosuojatarkistuksen otsikko, opiskelijalle esitetty kysymys ja nimetty vastaus. Arviointioikeuden omaaville käyttäjille näytetään lisäksi ohje vastauksen tulkinnasta ja jatkotoimista. Opiskelija näkee otsikon, kysymyksen ja vastauksen.

Taulun `assignsubmission_datacheck` kentät:

- `id`: tietueen tunniste.
- `assignment`: tehtävän tunniste (`assign.id`).
- `submission`: palautuksen tunniste (`assign_submission.id`); yksilöllinen.
- `answer`: vastaus (`yes` / `no` / `unsure`).
- `timemodified`: viimeisimmän muutoksen aikaleima.

Moodlen `assign_submission`-tietue yksilöi käyttäjän tai ryhmän sekä palautusyrityksen. Palautuksen sisältöä ei kopioida. Vastauksen muuttaminen korvaa aiemman arvon; muutoshistoriaa ei tallenneta.

## Keskeiset rajoitukset

- PHPUnit-testit kattavat versiot 4.5.15+ ja 5.3+; selaintestien tulokset ja testattujen lisäosaversioiden tiedot on eroteltu tiedostossa [QA.md](QA.md). Versioille 5.0, 5.1 ja 5.2 on tehty lähdekoodin ja rajapintojen tarkistus, mutta ei ajotestejä. Testit eivät varmista toimivuutta kaikissa käyttötilanteissa.
- Opiskelijan on vastattava kysymykseen ensimmäisellä palautuksen muokkauskerralla. Aiemmin tallennettu valinta palautetaan lomakkeelle myöhemmillä kerroilla.
- Kysymys on *ohjaava*. Se ei tarkista sisältöä automaattisesti, estä tietovuotoja (DLP) eikä anna oikeudellista lupaa tietojen käsittelyyn.
- Moodlen ryhmäpalautuksilla on yhteinen `assign_submission`-tietue. Ryhmäkohtaiset suostumukset ja henkilökohtaiset ilmoitukset vaativat erillisen suunnittelun.
- Mobiilisovelluksen, verkkopalvelurajapintojen ja pikapalautusten toiminta vaatii erilliset integraatiotestit. Kysymyksen ohittaminen voi olla mahdollista, joten pakollisuuden ei pidä väittää kattavan kaikkia palautustapoja.
- Oman tietokantataulun **varmuuskopiointi- ja palautustukea ei ole toteutettu**. Älä käytä lisäosaa tuotannossa tietoihin, joiden on säilyttävä kurssin palautuksessa varmuuskopiosta.
- Moodlen tietosuojarajapinnan (Privacy API) vienti- ja poistometodit on testattu PHPUnitilla. Koko Moodlen tietopyyntöprosessin toiminta ja ryhmäpalautusten tietosuojapyynnöt vaativat vielä erilliset hyväksymistestit.
- Lisäosa on erillinen Moodlen palautusvakuutuksesta eikä korvaa sitä.

## Ehdotetut hyväksymistestit

1. Luo tehtävä, jossa ovat käytössä tietosuojatarkistus ja tiedostopalautus.
2. Avaa opiskelijana **Lisää palautus**. Kysymyksestä on valittava **Kyllä**, **Ei** tai **En osaa sanoa**.
3. Yritä tallentaa valitsematta vastausta. Lomakkeen tulee ilmoittaa puuttuvasta vastauksesta.
4. Lataa testitiedosto ja tallenna vastauksella **Kyllä**. Tarkista vastaus opiskelijan palautusyhteenvedossa, opettajan arviointitaulukossa ja yksittäisen palautuksen arviointinäkymässä. Toista vastauksilla **Ei** ja **En osaa sanoa**.
5. Muokkaa palautusta. Tarkista, että tallennettu valinta näkyy lomakkeella. Vaihda vastausta ja varmista, että muutos tallentuu.
6. Tallenna pelkkä tietosuojavastaus ilman tiedostoa tai verkkotekstiä. Palautuksen tulee pysyä tyhjänä. Poista myös jo tallennetun palautuksen kaikki tiedostot ja verkkoteksti ja varmista, ettei jäljelle jäävä tietosuojavastaus kelpaa tehtäväsisällöksi.
7. Ota käyttöön erillinen lopullisen palautuksen lähetyspainike ja varmista, että kelvollinen vastaus sallii palautuksen lähettämisen. Ota tietosuojatarkistus käyttöön tehtävässä, jossa on jo tiedostopalautus mutta ei tietosuojavastausta. Lopullisen palautuksen tulee estyä ja näyttää kysymyksen virheilmoitus, kunnes opiskelija muokkaa palautusta ja vastaa kysymykseen.
8. Testaa erikseen uusi palautusyritys, opettajan arviointinäkymä, ryhmäpalautukset ja tietosuojarajapinta. Varmuuskopiointi- ja palautustukea ei ole, joten vastausten säilymiseen varmuuskopiosta palautettaessa ei saa luottaa.

## Moodlen PHPUnit-regressiotestit

Kun Moodlen PHPUnit-testiympäristö ja testitietokanta on määritetty, suorita Moodle 4.5:n juurihakemistossa:

```sh
vendor/bin/phpunit --test-suffix _test.php mod/assign/submission/datacheck/tests
```

Moodle 5.1:stä alkaen lisää testipolun alkuun `public/`. Testit kattavat tallennuksen, muokkaamisen, virheelliset vastaukset, lomakekentän, vastausten näyttämisen, ryhmän yhteisen vastauksen sekä tietosuojatietojen viennin ja poistamisen. Ne eivät korvaa selaimessa tehtäviä hyväksymistestejä.

## Behat-selaintestit

Lisäosan `tests/behat/declaration.feature` sisältää opiskelijan ja opettajan hyväksymisskenaariot. Aja ne vain erillisessä Moodlen Behat-ympäristössä, jonka testitietokanta ja testidatakansio on alustettu Moodlen ohjeiden mukaisesti:

```sh
vendor/bin/behat --config /polku/behatdata/behatrun/behat/behat.yml --tags=@assignsubmission_datacheck --format=progress
```

Testit käyttävät JavaScriptiä ja edellyttävät toimivaa Selenium-selainpalvelua. Tiedostossa [QA.md](QA.md) ovat testitapaukset, ajoympäristöt, tulokset ja vielä käsin tarkistettavat asiat.
