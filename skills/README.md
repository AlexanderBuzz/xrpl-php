# Agent Skill for the XRPL PHP SDK

An [Agent Skill](https://agentskills.io) that teaches AI coding agents how to build XRP Ledger applications with `hardcastle/xrpl_php`. It works with any agent that supports the Agent Skills standard (Claude Code, Codex CLI, Cursor, Gemini CLI and others).

## What it does

Once installed, the agent knows the SDK's actual API instead of guessing it from xrpl.js or xrpl-py: client and wallet, the autofill/sign/submit lifecycle and how to check a result, amounts and flags, the request class for every API method, every transaction model with its fields, tokens, NFTs, credentials, AMM and payment channels, and the errors it will run into.

## Installation

### Claude Code (via marketplace)

```bash
/plugin marketplace add AlexanderBuzz/xrpl-php
/plugin install xrpl-php@hardcastle-xrpl-php
```

### Any agent (via the skills CLI)

[`skills`](https://github.com/vercel-labs/skills) installs into Claude Code, Codex, Cursor, OpenCode and others, and records the install in `skills-lock.json` so `npx skills update` can refresh it:

```bash
npx skills add AlexanderBuzz/xrpl-php
```

### Manual

Copy `skills/xrpl-php/` into your agent's skill directory:

```bash
# Claude Code
cp -r skills/xrpl-php .claude/skills/xrpl-php

# Codex CLI
cp -r skills/xrpl-php .codex/skills/xrpl-php
```

## Structure

```
xrpl-php/
  SKILL.md                     # Core patterns; loaded when the skill activates
  references/                  # Loaded by the agent on demand
    transactions.md            # Every transaction model and its fields (generated)
    flags.md                   # Every flag constant with value and meaning (generated)
    methods.md                 # Every request class and its parameters (generated)
    recipes.md                 # Tokens, MPT, NFTs, credentials, AMM, payment channels
    troubleshooting.md         # Result codes, exceptions, Testnet quirks
```

The three generated files come from the code itself (`php skills/build.php`) and a test fails when they are out of date, so they cannot drift from the release they ship with. `SKILL.md` names the SDK version it describes in its frontmatter.
