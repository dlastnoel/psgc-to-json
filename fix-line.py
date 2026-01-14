# Remove line 57 (index 56) from the file
with open('app/Actions/Psgc/ImportPsgcData.php', 'r') as f:
    lines = f.readlines()

# Remove line at index 56 (which is line 57, counting from 1)
new_lines = lines[:56] + lines[57:]

with open('app/Actions/Psgc/ImportPsgcData.php', 'w') as f:
    f.writelines(new_lines)

print(f"Removed line 57. Total lines before: {len(lines)}, after: {len(new_lines)}")
