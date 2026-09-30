# Sources and licenses

The files under `references/` are the specifications of the XRP Ledger Standards,
copied verbatim from https://github.com/XRPLF/XRPL-Standards (the `README.md` of
each XLS directory) by `scripts/sync-xls-standards.py`. They are the work of their
respective authors and are redistributed under the repository's license:

    MIT License
    
    Copyright (c) 2021-2023 XRP Ledger Foundation (MTU XRP Ledger Trust)
    
    Permission is hereby granted, free of charge, to any person obtaining a copy
    of this software and associated documentation files (the "Software"), to deal
    in the Software without restriction, including without limitation the rights
    to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
    copies of the Software, and to permit persons to whom the Software is
    furnished to do so, subject to the following conditions:
    
    The above copyright notice and this permission notice shall be included in all
    copies or substantial portions of the Software.
    
    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
    OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
    SOFTWARE.

`SKILL.md`, `references/INDEX.md` and `scripts/` are the xrpl-standards agent skill
by Peersyst, taken from https://github.com/XRPLF/xrpl-go
(`.agents/skills/xrpl-standards`), MIT License, Copyright (c) 2024-present Peersyst
and the XRP Ledger developers.

This directory is not part of the Composer package (`.gitattributes`); it is here
for agents working on this repository. Check it against upstream with

    python3 .claude/skills/xrpl-standards/scripts/sync-xls-standards.py --dry-run

and refresh it by running the same command without `--dry-run`.
