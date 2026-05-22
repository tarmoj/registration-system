<?php

return [
    // Page titles
    'title_register'    => 'Playing-together Club – Registration',
    'title_attendance'  => 'Playing-together Club – Attendance',
    'title_unsubscribe' => 'Playing-together Club – Unsubscribe',

    // Intro text on registration page
    'intro' => '
<p><strong>KOOSMÄNGUKLUBI (Playing-together Club)</strong> is an opportunity to make music together. It is aimed primarily at adult amateur musicians, but everyone with an interest is welcome.</p>
<p>Unfortunately there is no piano at the venue — participants may only play instruments they can bring themselves.</p>
<p>Sessions take place on Saturdays/Sundays at Guitarium, Planeedi 5, Tallinn.<br>
Current schedule:<br>
<strong>Sun 11:00–12:00</strong> Fluent sight-readers (completed music school, intermediate or higher level; pieces are not difficult in themselves)<br>
<strong>Sat 17:00–18:00</strong> Improvisation Ensemble (suitable for all, level is not important)<br>
If more participants join: Simple pieces ensemble (a few years of study or self-taught). Time Sat 18:30 or Sun 12:30.</p>
<p>A session takes place if there are at least 3 participants in the sight-reading ensemble and at least 2 in the improvisation ensemble. I will send confirmation the evening before.</p>
<p>After registration you will receive a link to a table where you can mark your attendance.</p>
<p>If you have any questions, feel free to write: <a href="mailto:koosmanguklubi@gmail.com">koosmanguklubi@gmail.com</a></p>
<p>Thank you!<br>Tarmo</p>
',

    // Form labels
    'label_name'        => 'Name',
    'label_email'       => 'Email',
    'label_instrument'  => 'Instrument / Voice',
    'label_ensembles'   => 'I would like to participate in (you may choose multiple)',
    'label_experience'  => 'Describe your musical experience / background',
    'label_comments'    => 'Anything else you would like to add?',
    'btn_submit'        => 'Register',

    // Validation errors
    'error_name_required'  => 'Please enter your name.',
    'error_email_required' => 'Please enter a valid email address.',
    'error_email_invalid'  => 'The email address is not valid.',
    'error_email_exists'   => 'This email address is already registered.',
    'error_ensemble'       => 'Please select at least one ensemble.',

    // Success message after registration
    'success_register' => 'Thank you for registering! You can mark your attendance at: %s<br>A weekly reminder email will also be sent.',

    // Confirmation email
    'email_confirm_subject' => 'Koosmänguklubi – Registration confirmed',
    'email_confirm_body'    => "Thank you for registering!\n\nYou can mark your attendance here:\n%s\n\nA weekly reminder email will also be sent.\n\nTo unsubscribe from the club: %s\n\nFor questions write to: koosmanguklubi@gmail.com\n\nTarmo",

    // Attendance page
    'attendance_intro'    => 'Please mark <strong>+</strong> if you can attend, <strong>–</strong> if not, and leave empty if you do not know yet.',
    'login_prompt'        => 'Enter your email address:',
    'login_btn'           => 'Log in',
    'login_error'         => 'Email address not found. Please register first.',
    'vote_confirmed_yes'  => 'Marked: attending %s in ensemble %s.',
    'vote_confirmed_no'   => 'Marked: not attending %s in ensemble %s.',

    // Reminder email
    'reminder_subject'    => 'Reminder – Koosmänguklubi',
    'reminder_body'       => "Reminder: please confirm whether you will attend the Koosmänguklubi ensemble \"%s\" on %s:\n\nYES: %s\nNO:  %s\n",

    // Unsubscribe link label (shown on registration page and in emails)
    'unsubscribe_label'   => 'Unsubscribe from club',

    // Unsubscribe page
    'unsubscribe_confirm' => 'Would you like to unsubscribe from the Koosmänguklubi mailing list?',
    'unsubscribe_btn'     => 'Unsubscribe',
    'unsubscribe_done'    => 'Sorry to see you go. You are always welcome to rejoin by <a href="%s">registering again</a>.',
    'unsubscribe_invalid' => 'Invalid link.',
];
