<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
| Feature tests get the Laravel TestCase (app booted); Unit tests stay plain
| PHPUnit — the domain core (enums, PanWorkflow) must not need the framework.
*/

pest()->extend(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations & helpers
|--------------------------------------------------------------------------
| Custom expectations and global helpers for the suite go here.
*/
