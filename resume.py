from flask import Flask, render_template, request, redirect, send_file
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
import hashlib
import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
from flask import Flask, render_template, request, redirect, send_file, session
import bcrypt

app = Flask(__name__)

genai.configure(api_key="AIzaSyBGzNxOyGGugMqsy0coSYtnou119-tcbt0")

# MySQL Configuration
app.config["MYSQL_HOST"] = "localhost"
app.config["MYSQL_USER"] = "root"
app.config["MYSQL_PASSWORD"] = ""  # Update with your MySQL password
app.config["MYSQL_DB"] = "resumary"

mysql = MySQL(app)


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

    subject = "Resumary Account Password"
    body = f"""
    Hi dear employee,

    Here is your password in order to log in to your account and to see your profile and verify your information:

    Password: {password}

    If you have any questions or need assistance, feel free to contact us.

    Best regards,
    Resumary
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
        connection = mysql.connection
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


            

            sql_insert_query = "INSERT INTO resume3 (name, email, phoneNumber, skills, experience, education, pdf_content, password) VALUES (%s, %s, %s, %s, %s, %s, %s, %s)"
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


        cursor.close()
        connection.close()

        return redirect("/")

    # Pagination logic
    page = request.args.get("page", 1, type=int)
    per_page = 8
    start_index = (page - 1) * per_page

    cur = mysql.connection.cursor()
    cur.execute("SELECT COUNT(*) FROM resume3")
    total_resumes = cur.fetchone()[0]
    total_pages = ceil(total_resumes / per_page)

    cur.execute("SELECT * FROM resume3 LIMIT %s, %s", (start_index, per_page))
    result = cur.fetchall()
    cur.close()

    return render_template(
        "resume.html", data=result, page=page, total_pages=total_pages
    )


@app.route("/resume/view-pdf/<int:resume_id>")
def view_pdf(resume_id):
    try:
        cur = mysql.connection.cursor()
        cur.execute("SELECT pdf_content FROM resume3 WHERE id = %s", (resume_id,))
        pdf_content = cur.fetchone()
        cur.close()

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
        cur.execute("DELETE FROM resume3 WHERE id = %s", (resume_id,))
        mysql.connection.commit()
        cur.close()
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
                    "UPDATE resume3 SET name = %s, phoneNumber = %s, email = %s, skills = %s, experience = %s,  education = %s, status = %s, pdf_content = %s WHERE id = %s",
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
                    "UPDATE resume3 SET name = %s, phoneNumber = %s, email = %s, skills = %s, experience = %s,  education = %s, status = %s WHERE id = %s",
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
            cur.close()

            return redirect("/")
        except Exception as e:
            return f"An error occurred: {str(e)}"


if __name__ == "__main__":
    app.run(debug=True)
