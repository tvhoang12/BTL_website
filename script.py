import bcrypt

# Giả định mật khẩu mặc định
default_password = "123456"
hashed_password = bcrypt.hashpw(default_password.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')

# Đọc từ db.txt và ghi lại vào file mới
with open('db.txt', 'r', encoding='utf-8') as f:
    lines = f.readlines()

updated_lines = []

for line in lines:
    line = line.strip()
    if line.startswith("INSERT INTO students"):
        # Thêm trường mới: role_in_class, password_hash
        line = line.replace('INSERT INTO students (student_id, full_name, dob, gender, class, email, phone)',
                            'INSERT INTO users (user_id, full_name, dob, gender, class_id, email, phone, role_in_class, password_hash)')
        
        # Thêm dữ liệu cho role_in_class và password_hash
        if line.endswith(');'):
            line = line[:-2] + ", 'Student', '{}');".format(hashed_password)

    updated_lines.append(line)

# Ghi lại vào file mới (hoặc ghi đè)
with open('db_updated.sql', 'w', encoding='utf-8') as f:
    for line in updated_lines:
        f.write(line + '\n')

print("Đã xử lý xong và lưu vào file db_updated.sql")
