# Phase 9: SaaS enabled platform - CRUD independent tenant manager (Source Code Draft)

This document describes the orchestration layer responsible for provisioning and isolating multi-tenant instances on the underlying Docker host.

## 1. Implementation Overview

To convert the platform into a Software-as-a-Service (SaaS) provider, a system was developed to programmatically spin up independent Docker containers, databases, and routing logic for new "Child Services" (tenants) without relying on shared, partitioned databases.

## 2. Core Enhancements

### Orchestration via `ChildServiceManager`
The `ChildServiceManager.php` acts as the engine for all tenant lifecycle events (create, start, sync, stop, delete). It executes shell commands against the host system's Docker daemon.
- **Start**: Generates isolated `compose.yaml` and `.env` files within a `/tenants/<tenant-name>` directory.
- **Routing**: Automatically drops a reverse proxy `.conf` file into the host Nginx configurations and triggers a graceful `nginx -s reload`, routing `/<tenant-name>` directly to the newly spun container.
- **Sync**: Compares the expected database state (`absChildServices.csStatus`) against the real-world Docker container running state.

### Automated Admin Initialization
To prevent exposing raw database credentials or requiring plain-text passwords during the provisioning process, the `ChildServiceManager` maps a selected administrator from the *host* platform (`auPK`) directly into the new tenant.
When `start()` is invoked, it writes a `99_tenant_admin.sql` file containing the precise `INSERT` statement needed to establish the selected user as the root administrator of the isolated database upon first boot.

### Security and Validation
- `CreateChildServiceAction.php` enforces strict `alpha_dash` validation on the tenant namespace to prevent command injection and directory traversal attacks when generating the filesystem structures.
- The `ChildServiceDataProvider` automatically lists and provides Action Buttons (`Sync`, `Stop`, `Delete`) to allow visual monitoring of all provisioned environments.

## 3. Benefits & Outcomes
- **Strict Isolation**: Each tenant receives an entirely self-contained environment, maximizing security and minimizing "noisy neighbor" impacts.
- **Seamless Provisioning**: End users can provision full application stacks entirely from the web UI in under 30 seconds.
