-- =====================================================================================
-- Backfill the per-medicine review columns after importing an OLD paperless_prescription dump.
--
-- Order of use:
--   1. Import the old dump.
--   2. Add the new columns (php artisan migrate, or the manual ALTER TABLE statements).
--   3. Run this script ONCE.
--
-- Assumes the old rule of exactly ONE prescribed medicine per prescription, so every
-- prescription_id maps to a single prescribed_medicines.id. Step 0 lists any exception.
-- Requires MySQL 8+ / MariaDB 10.0.5+ (REGEXP_REPLACE).
-- =====================================================================================

-- 0. SANITY CHECK: must return no rows. If it does, stop and review those prescriptions.
SELECT prescription_id, COUNT(*) AS medicines
FROM prescribed_medicines
GROUP BY prescription_id
HAVING COUNT(*) > 1;

START TRANSACTION;

-- 1. Medicine inherits the days requested from its prescription
UPDATE prescribed_medicines m
JOIN prescriptions p ON p.id = m.prescription_id
SET m.days_requested = p.days_requested
WHERE m.days_requested IS NULL;

-- 2. Medicine status from the prescription's state (same precedence as the migration)
--    default approved (1)
UPDATE prescribed_medicines SET status = 1 WHERE decided_at IS NULL;

--    rejected (2): rx status 0 or rx was returned to the doctor
UPDATE prescribed_medicines m
JOIN prescriptions p ON p.id = m.prescription_id
SET m.status = 2
WHERE m.decided_at IS NULL
  AND (p.status = 0 OR EXISTS (SELECT 1 FROM returned_prescriptions r WHERE r.prescription_id = m.prescription_id));

--    pending (0): still under ICC review
UPDATE prescribed_medicines m
SET m.status = 0
WHERE m.decided_at IS NULL
  AND EXISTS (SELECT 1 FROM icc_reviews i WHERE i.prescription_id = m.prescription_id);

-- 3. Link concurrent therapy rows to the medicine of their prescription
UPDATE concurrent_therapy c
JOIN prescribed_medicines m ON m.prescription_id = c.prescription_id
SET c.prescribed_medicine_id = m.id
WHERE c.prescribed_medicine_id IS NULL;

-- 4. Link prescription logs to the medicine of their prescription
UPDATE prescription_logs l
JOIN prescribed_medicines m ON m.prescription_id = l.prescription_id
SET l.prescribed_medicine_id = m.id
WHERE l.prescribed_medicine_id IS NULL;

-- 5. Approved medicines: decision details from the latest approval row
UPDATE prescribed_medicines m
JOIN (SELECT prescription_id, MAX(id) AS id FROM concurrent_therapy
      WHERE date_approved IS NOT NULL GROUP BY prescription_id) latest ON latest.prescription_id = m.prescription_id
JOIN concurrent_therapy c ON c.id = latest.id
SET m.days_approved = c.days_approved,
    m.decided_by    = c.approved_by,
    m.decided_at    = c.date_approved
WHERE m.status = 1 AND m.decided_at IS NULL;

-- 6. Rejected medicines: reason and time from the latest log that sent the rx back to the doctor
--    (strips HTML tags and the leading "Rejected" word, like the migration does)
UPDATE prescribed_medicines m
JOIN (SELECT prescription_id, MAX(id) AS id FROM prescription_logs
      WHERE released_to = 'DOCTOR' GROUP BY prescription_id) latest ON latest.prescription_id = m.prescription_id
JOIN prescription_logs l ON l.id = latest.id
SET m.decision_remarks = NULLIF(TRIM(REGEXP_REPLACE(REGEXP_REPLACE(IFNULL(l.remarks, ''), '<[^>]*>', ''), '^[[:space:]]*Rejected[[:space:]]*', '')), ''),
    m.decided_by       = l.released_by,
    m.decided_at       = IFNULL(l.date_forwarded, l.created_at)
WHERE m.status = 2 AND m.decided_at IS NULL;

-- 7. Prescription review status from its medicines (withdrawn = 3 ignored)
--    0 = pending, 1 = partially approved, 2 = approved, 3 = rejected
UPDATE prescriptions p
JOIN (SELECT prescription_id,
             SUM(status = 0) AS pending, SUM(status = 1) AS approved, SUM(status = 2) AS rejected
      FROM prescribed_medicines WHERE status <> 3 GROUP BY prescription_id) s ON s.prescription_id = p.id
SET p.review_status = CASE
    WHEN s.approved = 0 AND s.rejected = 0 THEN 0
    WHEN s.pending = 0 AND s.rejected = 0 THEN 2
    WHEN s.pending = 0 AND s.approved = 0 THEN 3
    ELSE 1 END;

COMMIT;

-- 8. VERIFY: counts of what is still empty (expected 0 unless noted)
SELECT 'medicines without days_requested' AS item, COUNT(*) AS total FROM prescribed_medicines WHERE days_requested IS NULL
UNION ALL SELECT 'approved medicines without decided_at (no approval row found)', COUNT(*) FROM prescribed_medicines WHERE status = 1 AND decided_at IS NULL
UNION ALL SELECT 'rejected medicines without decided_at (no DOCTOR log found)', COUNT(*) FROM prescribed_medicines WHERE status = 2 AND decided_at IS NULL
UNION ALL SELECT 'concurrent_therapy not linked', COUNT(*) FROM concurrent_therapy WHERE prescribed_medicine_id IS NULL
UNION ALL SELECT 'prescription_logs not linked', COUNT(*) FROM prescription_logs WHERE prescribed_medicine_id IS NULL
UNION ALL SELECT 'prescriptions without review_status (no medicines)', COUNT(*) FROM prescriptions WHERE review_status IS NULL;

SELECT status, COUNT(*) AS medicines FROM prescribed_medicines GROUP BY status;
SELECT review_status, COUNT(*) AS prescriptions FROM prescriptions GROUP BY review_status;
