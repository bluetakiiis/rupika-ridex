<?php
/**
 * Purpose: Restrict routes to admin users only.
 * Website Section: Admin Area (dashboard, fleet, bookings, GPS/live tracking, profile/logout).
 * Developer Notes: Verify session role=admin, redirect to admin login on failure, and return 403 for API calls when unauthorized.
 */
