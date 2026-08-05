import os
import re

fpath = "/Users/wagyua5/Documents/Absensi/db_u836263092_ssll_20260721_013007_mysql_data.sql 2"
if not os.path.exists(fpath):
    for f in os.listdir("/Users/wagyua5/Documents/Absensi"):
        if "20260721" in f:
            fpath = os.path.join("/Users/wagyua5/Documents/Absensi", f)
            break

print("Processing file:", fpath)

with open(fpath, "r", encoding="utf-8", errors="ignore") as f:
    sql_text = f.read()

restoration_sql = []
restoration_sql.append("-- ========================================================\n")
restoration_sql.append("-- SCRIPT RESTORASI DATA TERHAPUS (17 & 18 JULI 2026)\n")
restoration_sql.append("-- Database: u836263092_ssll\n")
restoration_sql.append("-- ========================================================\n\n")
restoration_sql.append("SET FOREIGN_KEY_CHECKS=0;\n")
restoration_sql.append("SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n")

total_recovered_rows = 0
table_counts = {}

# Iterate over all table INSERT statements
for table_match in re.finditer(r"INSERT INTO `([^`]+)` VALUES\s*(.*?);", sql_text, re.DOTALL):
    table = table_match.group(1)
    values_str = table_match.group(2).strip()
    
    # Parse individual rows
    rows = []
    current_row = []
    in_string = False
    quote_char = None
    depth = 0
    
    for i, ch in enumerate(values_str):
        if not in_string:
            if ch in ("\"", "'"):
                in_string = True
                quote_char = ch
                current_row.append(ch)
            elif ch == "(":
                depth += 1
                current_row.append(ch)
            elif ch == ")":
                depth -= 1
                current_row.append(ch)
                if depth == 0:
                    rows.append("".join(current_row))
                    current_row = []
            elif depth > 0:
                current_row.append(ch)
        else:
            current_row.append(ch)
            if ch == quote_char:
                backslashes = 0
                idx = i - 1
                while idx >= 0 and values_str[idx] == "\\":
                    backslashes += 1
                    idx -= 1
                if backslashes % 2 == 0:
                    in_string = False
                    quote_char = None

    matched_rows = []
    for r in rows:
        # Check if row is specifically for 2026 July 17 or July 18
        if any(target in r for target in ["17-07-2026", "18-07-2026", "2026-07-17", "2026-07-18"]):
            matched_rows.append(r)
            
    if matched_rows:
        count = len(matched_rows)
        table_counts[table] = table_counts.get(table, 0) + count
        total_recovered_rows += count
        
        restoration_sql.append(f"-- Recovered {count} rows for table `{table}`\n")
        restoration_sql.append(f"INSERT IGNORE INTO `{table}` VALUES\n" + ",\n".join(matched_rows) + ";\n\n")

restoration_sql.append("SET FOREIGN_KEY_CHECKS=1;\n")

out_file = "/Users/wagyua5/Documents/Absensi/restore_17_18_juli_2026.sql"
with open(out_file, "w", encoding="utf-8") as f:
    f.writelines(restoration_sql)

print("\n--- RECOVERY SUMMARY ---")
for t, c in table_counts.items():
    print(f" - Table `{t}`: {c} rows recovered")
print(f"TOTAL RECOVERED: {total_recovered_rows} rows")
print("Saved to:", out_file)
