# Agent Skills Directory

This directory stores workspace skills following the agent-agnostic customization standard.

## Directory Structure

Each skill is organized in its own subdirectory inside `.agents/skills/`:

```text
.agents/skills/<skill-name>/
├── SKILL.md          # Required: Main instruction file with YAML frontmatter
├── scripts/          # Optional: Helper scripts and utilities
├── examples/         # Optional: Reference implementations
├── resources/        # Optional: Additional assets or templates
└── references/       # Optional: Detailed documentation or manuals
```

## `SKILL.md` Specification

A skill entry point must be a `SKILL.md` file starting with YAML frontmatter:

```markdown
---
name: skill-name
description: >-
  Describe what the skill does and when the agent should use it.
---

# Skill Title

Clear, step-by-step instructions and runbooks for the agent.
```
