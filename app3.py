from flask import Flask, json, redirect,session, render_template, request, send_file, url_for
from dotenv import load_dotenv
import os
import io
from PIL import Image
import pdf2image
import mysql.connector
import google.generativeai as genai
import re
from sklearn.feature_extraction.text import CountVectorizer, TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
from flask_mysqldb import MySQL
from io import BytesIO
import tempfile
import os
from pyresparser import ResumeParser
from flask import request
from math import ceil
import google.generativeai as genai
import pdf2image
import random
import string
import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
import bcrypt


app = Flask(__name__)
app.secret_key = "secret"

genai.configure(api_key="AIzaSyAcOUJklUAcRwMk4bzcfuWcHBNY_QuPEsA")

# Connect to MySQL database
connection = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database="res",
)
cursor = connection.cursor()

def set_session(value):
    session['data'] = value
    return 'Session variable set'

def get_session():
    data = session.get('data')
    if data:
        return 'Session variable value: ' + data
    else:
        return 'Session variable not set'

def retrieve_all_pdfs_from_database():
    cursor.execute(
        "SELECT name, id, skills, experience, education, evaluation FROM resume ORDER BY upload_date DESC"
    )
    pdf_data_list = cursor.fetchall()
    return pdf_data_list

def extract_numbers(text):
    numbers = re.findall(r"\d+", text)
    return "".join(numbers)

def pdf_to_image(pdf_data):
    image = pdf2image.convert_from_bytes(pdf_data)[0]  # Get the first page
    return image

def get_gemini_response1(input_text, prompt):
    model = genai.GenerativeModel(model_name="gemini-1.0-pro-001")
    response = model.generate_content([input_text, prompt])
    return response.text
@app.route("/job")
def home():
    return render_template('job.php')

@app.route('/job/submit', methods=['POST'])
def job_submit():
    job_description = request.form["job_description"]
    prompt_job = """you are responsible of the HR department of a company you received the previous job description, based on it, you will generate the keywords you value the most that should be present in a potential worker resume (give only keywords do not say thanks)."""
    job_keywords = get_gemini_response1(job_description, prompt_job)
    
    keywords_list = job_keywords.split(", ")  # Assuming keywords are comma-separated
    tfidf_vectorizer = TfidfVectorizer()
    job_keywords_vector = tfidf_vectorizer.fit_transform([" ".join(keywords_list)])
    
    pdf_data_list = retrieve_all_pdfs_from_database()
    results = []
    
    for name, ID, skills, experience, education, evaluation in pdf_data_list:
        resume = f"{skills} {experience} {education}"
        resume_vector = tfidf_vectorizer.transform([resume])
        similarity = cosine_similarity(job_keywords_vector, resume_vector)[0][0]
        
        if evaluation != -1:
            similarity = (similarity + (evaluation / 10)) / 2
        
        if similarity >= 0.2:
            results.append({
                "name": name,
                "resume_id": ID,
                "similarity": similarity,
                "view_pdf_url": url_for("view_pdf", resume_id=ID)
            })
    
    results = sorted(results, key=lambda x: -x['similarity'])
    best_match = results[0]
    
    return render_template("job.php", results=best_match, job_description=job_description)



@app.route("/")
def main():
    id = request.args.get('id')
    print("ID:", id)
    if id:
        return render_template('index.php', id=id)
    
@app.route("/view-pdf/<int:resume_id>")
def view_pdf(resume_id):
    try:
        cur = connection.cursor()
        cur.execute("SELECT pdf_content FROM resume WHERE id = %s", (resume_id,))
        pdf_content = cur.fetchone()

        if pdf_content:
            return send_file(io.BytesIO(pdf_content[0]), mimetype="application/pdf")
        else:
            return "No PDF available for this resume ID."
    except Exception as e:
        return f"An error occurred: {str(e)}"
    
@app.route('/submit', methods=['POST'])
def submit():
    results = []
    # Retrieve form data
    id= request.form['id']
    project_title = request.form["project_title"]
    project_description = request.form["project_description"]
    print("ID:", id)
    # Correct SQL query with placeholders
    query = """
    INSERT INTO project (id, description, title, state, u_id)
    VALUES (%s, %s, %s, %s, %s)
    """

    # Parameters tuple, with None for po_id if it's not being used
    params = (" " , project_description, project_title, 0, id)
    cursor.execute(query, params)
    connection.commit()  # Commit the transaction
    new_project_id = cursor.lastrowid
    project_description_text = f"job description: {project_description}"
        


    print("Job Description:", project_description_text)  # Log job description

        # Get roles and number of workers
    prompt_roles = """you are responsible of the HR department of a company you received the previous project description, based on it, you will generate a team with roles and the number of workers required for each role in the form of 'role:      '."""
    roles_text = get_gemini_response1(project_description_text, prompt_roles)
    print("Roles Prompt:", prompt_roles)  # Log roles prompt
    print("Roles Text:", roles_text)  # Log roles response

        # Parse roles and number of workers
    roles = {}
    for line in roles_text.strip().split('\n'):
        if ':' in line:
            role, number = line.split(':', 1)
            role = role.strip().replace('*', '').replace('**', '').strip()
            try:
                number = re.findall(r'\d+', number.strip())[0]  # Extract first number
                roles[role] = int(number)
            except (ValueError, IndexError):
                print(f"Invalid number format for role: {role.strip()} - {number.strip()}")

        # Get skills/education/experience keywords for each role
    role_keywords = {}
    for role in roles.keys():
        prompt_keywords = f"""Generate the skills, education, and experience keywords for the role of {role}."""
        keywords = get_gemini_response1(project_description_text, prompt_keywords)
        print(f"Keywords Prompt for {role}:", prompt_keywords)  # Log keywords prompt
        print(f"Keywords for {role}:", keywords)  # Log keywords response
        role_keywords[role] = keywords

    pdf_data_list = retrieve_all_pdfs_from_database()
        
    for role, number in roles.items():
        role_results = []
        tfidf_vectorizer = TfidfVectorizer()
        role_keywords_vector = tfidf_vectorizer.fit_transform([role_keywords[role]])

        for name, ID, skills, experience, education, evaluation in pdf_data_list:
            resume = f"{skills} {experience} {education}"
            resume_vector = tfidf_vectorizer.transform([resume])
            similarity = cosine_similarity(role_keywords_vector, resume_vector)[0][0]
            if evaluation != -1:
                similarity = (similarity + (evaluation / 10)) / 2
            if similarity >= 0.2:
                role_results.append({
                    "name": name,
                    "resume_id": ID,
                    "role": role,
                    "view_pdf_url": url_for("view_pdf", resume_id=ID),
                    "similarity" : similarity,
                    "project_id" : new_project_id
                })

        role_results = sorted(role_results, key=lambda x: -x['similarity'])[:number]
        results.append({ "role": role, "resumes": role_results })

    return render_template("index.php", results=results, id=request.args.get('id'))



@app.route("/projects/validate_team", methods=["GET"])
def validate_team():

    results = request.args.getlist('results')
    print(results)
    for item in results:
        # Parse the string into a dictionary
        item_dict = json.loads(item.replace("'", '"'))
        print(item_dict[0]["role"])
        # Extract resume_id and project_id from each resume in 'resumes' list
        for i in item_dict:
            role = i["role"]
            for d in i["resumes"] :
                
            # Insert data into the 'teams' table
                cursor.execute(
                "INSERT INTO teams (r_id, p_id, evaluation, role) VALUES (%s, %s, %s, %s)",
                (d["resume_id"], d["project_id"], '-1', role)
            )
        

    return redirect(url_for('projects'))


genai.configure(api_key="AIzaSyBGzNxOyGGugMqsy0coSYtnou119-tcbt0")

# Function to generate a random password
def generate_password(length=12):
    characters = string.ascii_letters + string.digits + string.punctuation
    return "".join(random.choice(characters) for i in range(length))


# Function to hash a password using bcrypt with the same algorithm as PHP's PASSWORD_DEFAULT
def hash_password(password):
    hashed_password = bcrypt.hashpw(password.encode(), bcrypt.gensalt(rounds=12))
    return hashed_password.decode()  # Convert bytes to string


def get_gemini_response2(pdf_content, prompt):
    model = genai.GenerativeModel("gemini-pro-vision")
    response = model.generate_content([pdf_content, prompt])
    return response.text


# Function to convert PDF data to image
def pdf_to_image(pdf_data):
    images = pdf2image.convert_from_bytes(pdf_data)
    return images[0]


# Function to convert image to bytes
def image_to_bytes(image):
    byte_stream = BytesIO()
    image.save(byte_stream, format="JPEG")  # Save image as JPEG format
    return byte_stream.getvalue()


# Function to extract resume information
def extract_resume_info(pdf_content):
    with tempfile.NamedTemporaryFile(delete=False, suffix=".pdf") as temp_file:
        temp_file.write(pdf_content)
        temp_file_path = temp_file.name

    resume_data = ResumeParser(temp_file_path).get_extracted_data()

    os.unlink(temp_file_path)

    name = resume_data.get("name", None)
    email = resume_data.get("email", None)
    mobile_number = resume_data.get("mobile_number", None)

    pdf_content = pdf_to_image(pdf_content)

    input_prompt1 = """
    You are an experienced analyser and you understand both eglish and french. Your task is to review the provided resume extract the experience, and don't give me any additional information, i need only the experience
        """
    input_prompt2 = """
    You are an experienced analyser you understand both eglish and french. Your task is to review the provided resume extract the skills, and don't give me any additional information, i need only the skills.
        """
    input_prompt3 = """
    You are an experienced analyser you understand both eglish and french. Your task is to review the provided resume extract the education, and don't give me any additional information, i need only the education.
        """
    experience = get_gemini_response2(pdf_content, input_prompt1)
    skills = get_gemini_response2(pdf_content, input_prompt2)
    education = get_gemini_response2(pdf_content, input_prompt3)
    pdf_bytes = image_to_bytes(pdf_content)

    return name, email, mobile_number, experience, skills, education

def send_email(recipient_email, password):
    """Send an email with the generated password to the employee."""
    sender_email = "sarabelhadj31000@gmail.com"
    sender_password = "vdqv onlj lrei ycon"

    subject = "resumary Account Password"
    body = f"""
    Hi dear employee,

    Here is your password in order to log in to your account and to see your profile and verify your information:

    Password: {password}

    If you have any questions or need assistance, feel free to contact us.

    Best regards,
    resumary
    """    
    msg = MIMEText(body)
    msg['Subject'] = subject
    msg['From'] = sender_email
    msg['To'] = recipient_email

    try:
        with smtplib.SMTP_SSL('smtp.gmail.com', 465) as server:
            server.login(sender_email, sender_password)
            server.sendmail(sender_email, recipient_email, msg.as_string())
        print("Email sent successfully.")
    except Exception as e:
        print(f"Failed to send email: {e}")
        
# Route for uploading a resume
@app.route("/resume/", methods=["GET", "POST"])
def upload_resume():
    if request.method == "POST":
        cursor = connection.cursor()

        uploaded_files = request.files.getlist("resume")

        for uploaded_file in uploaded_files:
            pdf_content = uploaded_file.read()
            name, email, mobile_number, experience, skills, education = (
                extract_resume_info(pdf_content)
            )

            # You can insert the extracted information into your database here
            # For demonstration, I'm printing the extracted information
            print("Name:", name)
            print("Phone Number:", mobile_number)

            # Generate a random password
            password = generate_password()

            # Hash the password
            hashed_password = hash_password(password)
            email = email.rstrip(',')

            sql_insert_query = "INSERT INTO resume (name, email, phoneNumber, skills, experience, education, pdf_content, password) VALUES (%s, %s, %s, %s, %s, %s, %s, %s)"
            cursor.execute(
                sql_insert_query,
                (
                    name,
                    email,
                    mobile_number,
                    skills,
                    experience,
                    education,
                    pdf_content,
                    hashed_password, 
                ),
            )
            connection.commit()
            send_email(email, password)

        return redirect("/")

    # Pagination logic
    page = request.args.get("page", 1, type=int)
    per_page = 8
    start_index = (page - 1) * per_page
    cursor = connection.cursor()
    cursor.execute("SELECT COUNT(*) FROM resume")
    total_resumes = cursor.fetchone()[0]
    total_pages = ceil(total_resumes / per_page)

    cursor.execute("SELECT * FROM resume LIMIT %s, %s", (start_index, per_page))
    result = cursor.fetchall()

@app.route("/resume")
def resume():

    page = request.args.get("page", 1, type=int)
    per_page = 8
    start_index = (page - 1) * per_page

    cur = connection.cursor()
    cur.execute("SELECT COUNT(*) FROM resume")
    total_resumes = cur.fetchone()[0]
    total_pages = ceil(total_resumes / per_page)

    cur.execute("SELECT * FROM resume LIMIT %s, %s", (start_index, per_page))
    result = cur.fetchall()

    return render_template("resume.html", data=result, page=page, total_pages=total_pages)


@app.route("/resume/view-pdf/<int:resume_id>")
def view_pdff(resume_id):
    try:
        cur = mysql.connection.cursor()
        cur.execute("SELECT pdf_content FROM resume WHERE id = %s", (resume_id,))
        pdf_content = cur.fetchone()
  

        if pdf_content:
            return send_file(BytesIO(pdf_content[0]), mimetype="application/pdf")
        else:
            return "No PDF available for this resume ID."
    except Exception as e:
        return f"An error occurred: {str(e)}"


@app.route("/resume/delete/<int:resume_id>", methods=["GET"])
def delete_resume(resume_id):
    try:
        cur = mysql.connection.cursor()
        cur.execute("DELETE FROM resume WHERE id = %s", (resume_id,))
        mysql.connection.commit()
    
        return redirect("/")
    except Exception as e:
        return f"An error occurred: {str(e)}"


@app.route("/resume/modify/<int:resume_id>", methods=["POST"])
def modify_resume(resume_id):
    if request.method == "POST":
        try:
            # Get status, name, phone number, and designation from form data
            status = request.form["status"]
            name = request.form["name"]
            phone_number = request.form["phoneNumber"]
            email = request.form["email"]
            skills = request.form["skills"]
            experience = request.form["experience"]
            education = request.form["education"]
            # Check if a new CV is provided
            pdf_content = None
            if "resume-modify" in request.files:
                uploaded_file = request.files["resume-modify"]
                pdf_content = uploaded_file.read()

            # Update status, name, phone number, designation, and PDF content if provided
            cur = mysql.connection.cursor()
            if pdf_content:
                cur.execute(
                    "UPDATE resume SET name = %s, phoneNumber = %s, email = %s, skills = %s, experience = %s,  education = %s, status = %s, pdf_content = %s WHERE id = %s",
                    (
                        name,
                        phone_number,
                        email,
                        skills,
                        experience,
                        education,
                        status,
                        pdf_content,
                        resume_id,
                    ),
                )
            else:
                cur.execute(
                    "UPDATE resume SET name = %s, phoneNumber = %s, email = %s, skills = %s, experience = %s,  education = %s, status = %s WHERE id = %s",
                    (
                        name,
                        phone_number,
                        email,
                        skills,
                        experience,
                        education,
                        status,
                        resume_id,
                    ),
                )
            mysql.connection.commit()
    

            return redirect("/")
        except Exception as e:
            return f"An error occurred: {str(e)}"


if __name__ == "__main__":
    app.run(debug=True)