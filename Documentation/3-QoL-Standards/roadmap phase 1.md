# Phase 1: Review Configuration Variables [COMPLETED]

## Description
The primary goal of Phase 1 was to establish a single source of truth for all application configuration variables, and to standardise the application's external network footprint. Initially, the goal included moving the web application from port `8443` to the standard HTTPS port `443`.

## Implementation Details
- **Port Normalization Strategy Shift**: During analysis, the system's external port restriction was bypassed not by altering the core Docker topology to expose `443`, but rather by introducing a flexible `EXTERNAL_PORT` environment variable. This allows the application to dynamically construct correct absolute URLs (such as in the Action Tracker links and `Hyperlink` redirects) regardless of the host's binding.
- **Environment Configuration**: The `SystemConfigController` was maintained as the central registry, accurately injecting the `EXTERNAL_PORT` and other environment variables across the broader Event Sourcing architecture.
