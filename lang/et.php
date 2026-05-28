<?php

return [
    // Page titles
    'title_register'    => 'Koosmänguklubi – registreerumine',
    'title_attendance'  => 'Koosmänguklubi – osalemine',
    'title_unsubscribe' => 'Koosmänguklubi – loobumine',

    // Intro text on registration page
    'intro' => '
<p><strong>KOOSMÄNGUKLUBI</strong> on võimalus mängida üheskoos. See on mõeldud eelkõige täiskasvanutest amatöörmuusikutele, kuid teretulnud on kõik, kel huvi.</p>
<p>Kahjuks ei ole toimumiskohas klaverit ja osaleda saab ainult nende pillidega, mida osaleja saab ise kohale tuua.</p>
<p>Koosmängud toimuvad laupäeviti/pühapäeviti Guitarium ruumides, Planeedi 5, Tallinn.<br>
Praeguse seisuga:<br>
<strong>P 11.00–12.00</strong> Ladusalt noodist lugejad (lõpetanud muusikakooli, keskastme, kõrgkooli vms. Lood ei ole iseenesest rasked)<br>
<strong>L 17.00–18.00</strong> Improvisatsiooniansambel (sobib kõikidele, tase pole määrav)<br>
Kui liitub veel huvilisi: Lihtsate lugude ansambel (õppinud mõned aastad või omal käel). Aeg L 18.30 või P 12.30.</p>
<p>Koosmäng toimub, kui noodist mängimise ansamblis on vähemalt 3 osalist ning improvisatsiooniansamblis vähemalt 2 osalist. Eelneval õhtul saadan kinnituse.</p>
<p>Peale registreerumist saate lingi tabelile, kus märkida oma osavõtt.</p>
<p>Kui on küsimusi, kirjutage julgelt: <a href="mailto:koosmanguklubi@gmail.com">koosmanguklubi@gmail.com</a></p>
<p>Aitäh!<br>Tarmo</p>
',

    // Form labels
    'label_name'        => 'Nimi',
    'label_email'       => 'Email',
    'label_instrument'  => 'Instrument / Hääl',
    'label_ensembles'   => 'Sooviksin osaleda (võite valida mitu)',
    'label_experience'  => 'Kirjeldage oma senist muusikalist kogemust / tausta',
    'label_comments'    => 'Mida soovite lisada?',
    'btn_submit'        => 'Registreeru',

    // Validation errors
    'error_name_required'  => 'Palun sisestage oma nimi.',
    'error_email_required' => 'Palun sisestage kehtiv e-posti aadress.',
    'error_email_invalid'  => 'E-posti aadress on vigane.',
    'error_email_exists'   => 'See e-posti aadress on juba registreeritud.',
    'error_ensemble'       => 'Palun valige vähemalt üks ansambel.',

    // Success message after registration
    'success_register' => 'Aitäh registreerimast! Osalemisi saate sisse kanda sellel lehel: %s<br>Igal nädalal saadetakse ka meeldetuletav e-mail.',

    // Confirmation email
    'email_confirm_subject' => 'Koosmänguklubi – registreerumine kinnitatud',
    'email_confirm_body'    => "Aitäh registreerimast!\n\nEdasisi osalemisi saate sisse kanda sellel lehel:\n%s\n\nIgal nädalal saadetakse ka meeldetuletav e-mail.\n\nKlubist lahkumiseks: %s\n\nKüsimuste korral kirjutage: koosmanguklubi@gmail.com\n\nTarmo",

    // Attendance page
    'attendance_intro'    => 'Palun märkige <strong>+</strong> kui saate tulla, <strong>–</strong> kui mitte ning jätke tühjaks kui veel ei tea.',
    'login_prompt'        => 'Sisestage oma e-posti aadress:',
    'login_btn'           => 'Sisene',
    'login_error'         => 'E-posti aadressi ei leitud. Palun registreeruge esmalt.',
    'vote_confirmed_yes'  => 'Märgitud: tulen %s ansamblisse %s.',
    'vote_confirmed_no'   => 'Märgitud: ei tule %s ansamblisse %s.',

    // Reminder email
    'reminder_subject'    => 'Meeldetuletus – Koosmänguklubi',
    'reminder_body'       => '<p>Meeldetuletus: palun kinnitage, kas osalete Koosmänguklubi ansamblis &ldquo;<strong>%s</strong>&rdquo; kuupäeval <strong>%s</strong>:</p><p><a href="%s" style="display:inline-block;padding:0.5rem 1.4rem;background:#2a7;color:#fff;text-decoration:none;border-radius:4px;font-weight:bold">JAH</a>&nbsp;&nbsp;<a href="%s" style="display:inline-block;padding:0.5rem 1.4rem;background:#a33;color:#fff;text-decoration:none;border-radius:4px;font-weight:bold">EI</a></p>',
    'reminder_unsubscribe' => 'Listist lahkumiseks vajutage <a href="%s">siia</a>.',

    // Unsubscribe link label (shown on registration page and in emails)
    'unsubscribe_label'   => 'Klubist lahkumine',

    // Unsubscribe page
    'unsubscribe_confirm'     => 'Kas soovite loobuda Koosmänguklubi meililistist?',
    'unsubscribe_btn'         => 'Loobu',
    'unsubscribe_done'        => 'Kahju, et lahkute. Olete alati teretulnud uuesti liituma <a href="%s">registreerumisel</a>.',
    'unsubscribe_invalid'     => 'Vigane link.',
    'unsubscribe_email_prompt'=> 'Sisestage oma e-posti aadress:',
    'unsubscribe_email_btn'   => 'Saada link',
    'unsubscribe_email_sent'  => 'Lahkumise link saadetakse teie e-mailile %s.',
    'unsubscribe_email_error' => 'E-posti aadressi ei leitud.',
    'unsubscribe_email_subject' => 'Koosmänguklubi – lahkumise link',
    'unsubscribe_email_body'  => "Lahkumiseks klõpsake sellel lingil:\n%s\n",
];
