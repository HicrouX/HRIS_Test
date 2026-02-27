---
name: figma-mcp-connector
description: Bridges Figma designs and React code using Figma MCP. Use when generating React components (Tailwind CSS) from Figma frames while maintaining HRIS-specific business logic (e.g., role-based access, time calculations).
---

# Figma MCP Connector

This skill provides specialized prompt templates for translating Figma designs into the iREPLY HRIS React frontend.

## Workflows

### 1. Identify the Component Type
Determine if the Figma frame is a Dashboard, a Table, or a Form.

### 2. Apply the Template
Read [PROMPT_TEMPLATES.md](references/PROMPT_TEMPLATES.md) and select the corresponding template.

### 3. Inject Business Logic
Always append the following logic to your Figma MCP prompts:
- **Role Check**: Only show management views if `role_id >= 2`.
- **Lunch Rule**: Shift > 5 hours = -1 hour.
- **Styling**: Force `Tailwind CSS v3` with `Slate` palette and `Sky-500` accent.

## Reference Files
- [PROMPT_TEMPLATES.md](references/PROMPT_TEMPLATES.md): Detailed prompts for v0 and Figma MCP.
