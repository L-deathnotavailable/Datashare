# Interface DataShare

L'interface est accessible via Nginx sur http://localhost:8088. Les formulaires appellent l'API sous `/api`.

```powershell
docker compose -p datashare exec frontend npm run build
docker compose -p datashare exec frontend npm run lint
```

L'installation et les tests sont décrits dans le README et TESTING.md à la racine.
