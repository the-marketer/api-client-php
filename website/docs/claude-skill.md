---
title: Claude Skill (PHP - AI assistant)
---

The official **Claude skill** for the PHP package — `themarketer/api-client-php` — teaches Claude how to use this package correctly: module APIs, payload shapes, REST vs tracking gateways, Laravel wiring, and exception handling. It reduces guesswork when you integrate or debug in Claude **Code**, **Claude Desktop**, or **Claude** on the web.

Maintained in GitHub: [the-marketer/claude-docs-skill](https://github.com/the-marketer/claude-docs-skill).

:::tip Using Node.js instead of PHP?
This page covers the **PHP** skill. If you work with the Node.js package, there is a separate skill — `@themarketer/api-client-node` — published in the **same repository** ([the-marketer/claude-docs-skill](https://github.com/the-marketer/claude-docs-skill)). Install that one instead for Node projects.
:::

## What the skill helps with

- Correct PHP for all client modules (`orders()`, `subscribers()`, `campaigns()`, `products()`, `transactionals()`, `reports()`, `events()`, `coupons()`, `loyalty()`, `reviews()`, `mobilePush()`, and related utilities).
- Choosing the **REST gateway** vs **tracking gateway** appropriately.
- Payload fields: names, types, required vs optional, and enums where applicable.
- Laravel: service provider, facade, and the `themarketer` mail transport.
- Catching errors with the right exception types in a sensible order.

When your question relates to this package, Claude can load the skill automatically so answers follow the same schemas and conventions as these docs.

## Releases

Packaged `.skill` files and install notes are published on **[GitHub Releases](https://github.com/the-marketer/claude-docs-skill/releases)**. Install using the **latest release** for up-to-date content; older tags remain available if you need to pin a version (for example [v0.1.0](https://github.com/the-marketer/claude-docs-skill/releases/tag/v0.1.0)).

## Install: Claude Code (recommended)

From a Claude Code session:

```text
/plugin marketplace add the-marketer/claude-docs-skill
/plugin install themarketer-api-client-php@themarketer-api-client
/reload-plugins
```

Confirm it appears when you ask what skills are available (`themarketer-api-client-php`). The skill may be addressed as `themarketer-api-client-php:themarketer-api-client-php` (plugin name + skill name); you typically do not need to type a slash command—Claude loads it when the topic matches.

## Install: Claude Desktop / Claude web

1. Download **`themarketer-api-client-php.skill`** from the [latest GitHub Release](https://github.com/the-marketer/claude-docs-skill/releases/latest).
2. In Claude: **Customize → Skills → + → Create skill**.
3. Upload the `.skill` file and enable it.
4. Ensure **code execution** is allowed where your account/org requires it (Settings → Capabilities, or org-level Skills settings for Team/Enterprise).

## Install: manual (clone skill folder)

**Personal (all projects):**

```bash
mkdir -p ~/.claude/skills
git clone https://github.com/the-marketer/claude-docs-skill /tmp/tm-skill
cp -r /tmp/tm-skill/skills/themarketer-api-client-php ~/.claude/skills/
rm -rf /tmp/tm-skill
```

**Single project:**

```bash
cd /path/to/your/project
mkdir -p .claude/skills
git clone https://github.com/the-marketer/claude-docs-skill /tmp/tm-skill
cp -r /tmp/tm-skill/skills/themarketer-api-client-php .claude/skills/
rm -rf /tmp/tm-skill
```

You can also unzip a downloaded `.skill` archive into your skills directory.

## Example prompts

These illustrate the kinds of questions that trigger useful, schema-aware answers:

- “Help me sync an order with `themarketer/api-client` from PHP.”
- “I'm getting a `ValidationException` on `viewProduct`—what's wrong?”
- “Set up the Laravel mail transport for The Marketer.”
- “Push 200 subscribers in bulk through The Marketer.”
- “What's the payload for `campaigns()->create` with `type=email`?”

You do not always need to mention the package name explicitly; related wording about The Marketer APIs or Laravel integration often suffices.

## Contributing and versioning

The PHP skill content lives under `skills/themarketer-api-client-php/` in the repository (the Node.js skill lives alongside it under `skills/themarketer-api-client-node/`). The skill project follows SemVer; suggest improvements via pull requests on [the-marketer/claude-docs-skill](https://github.com/the-marketer/claude-docs-skill).
