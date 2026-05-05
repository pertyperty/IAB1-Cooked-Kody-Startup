# Manual QA Checklist

Follow this checklist as part of manual verification before merging or deploying.

- [ ] Environment: XAMPP or PHP+MySQL running, DB created and `database.sql` applied.
- [ ] Uploads directory exists and is writable: `kody/assets/uploads/`.
- [ ] Create user via Admin: verify `profile_picture` upload stores file and DB stores filename.
- [ ] Create/Update records across major tables: `users`, `courses`, `challenges`, `submissions`, `payments`.
- [ ] Search and pagination in Admin panels work for large datasets.
- [ ] Verify FK selects populate and reflect correct labels.
- [ ] Verify server-side validation rejects invalid email, numeric, or FK values.
- [ ] Verify flash messages appear and auto-dismiss.
- [ ] Smoke test student flows: register, login, enroll, submit code, view XP.
- [ ] Smoke test admin flows: CRUD pages, edit, delete, and role-based access control.
