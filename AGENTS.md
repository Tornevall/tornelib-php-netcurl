# NetCurl repository guidance

- Treat GitHub as the source of truth. Check existing issues, PRs, relevant source code and branches before making changes.
- The 6.1 line is legacy maintenance. Preserve support for the existing PHP compatibility matrix and avoid modern PHP syntax in new 6.1 source code.
- Never change or remove existing `SimpleDomParser` behavior, legacy XPath method signatures or rendered return structures as a side effect of new DOM features.
- New parsing, model or discovery features must be opt-in. Keep `GenericParser` legacy dispatch precedence intact.
- Keep NetCurl generic: application-specific RSS field inference, persistence and UI belong in the consuming application, not in this library.
- Parse untrusted XML/HTML without remote entity loading. Preserve libxml error handling state and normalize failures to useful exceptions.
- Add deterministic regression tests for changes affecting DOM/XPath, namespaces, PHP compatibility, legacy interfaces, or transport selection.
- Keep test fixtures isolated by behavior and identifiers. Never rely solely on PHPUnit docblock process-isolation annotations for test independence, because newer PHPUnit releases may ignore them.
- Prefer the smallest meaningful checks before broader compatibility testing. Do not run live HTTP/provider tests for local parser-only changes.
- Update `README.md` and `CHANGELOG.md` when behavior or consumer-facing contracts change. Include an issue link in every new changelog item. Maintained public end-user documentation lives in Tornevall Tools.
- Never put credentials, private tokens, passwords or other secrets in repository content, diagnostics or test fixtures.
