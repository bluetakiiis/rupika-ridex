<?php
/**
 * Purpose: Ensure routes are accessible only to authenticated users.
 * Website Section: Cross-cutting (protect user dashboard, booking actions, admin area).
 * Developer Notes: Check session/user role, redirect or respond 401 for APIs, and optionally enforce CSRF.
 */
