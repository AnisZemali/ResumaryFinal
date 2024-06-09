import json
import subprocess
import pymysql

# Connect to MySQL database
servername = "localhost"
username = "root"
password = ""
dbname = "res"

conn = pymysql.connect(host=servername, user=username, password=password, database=dbname)

# Check connection
if conn.connect_errno:
    print("Connection failed:", conn.connect_error)
    exit()

# Fetch job descriptions from the database
sql = "SELECT job_descroption FROM job_description"
with conn.cursor() as cursor:
    cursor.execute(sql)
    job_descriptions = [row[0] for row in cursor.fetchall()]

conn.close()

# Function to extract information from the job description
def extract_information(description):
    # Execute Python script with job description as input
    command = ["python", "python_.py", description]
    output = subprocess.check_output(command)

    # Decode the JSON output
    info = json.loads(output.decode("utf-8"))

    return info

# Process each job description
output = [extract_information(description) for description in job_descriptions]

# Output results
print(json.dumps(output))
