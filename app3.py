from flask import Flask,session, render_template, request, send_file, url_for
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

app = Flask(__name__)
app.secret_key = "secret"

genai.configure(api_key="AIzaSyAcOUJklUAcRwMk4bzcfuWcHBNY_QuPEsA")

# Connect to MySQL database
connection = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database="resumary",
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
        "SELECT name, id, skills, experience, education, evaluation FROM resume3 ORDER BY upload_date DESC"
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

@app.route("/view-pdf/<int:resume_id>")
def view_pdf(resume_id):
    try:
        cur = connection.cursor()
        cur.execute("SELECT pdf_content FROM resume3 WHERE id = %s", (resume_id,))
        pdf_content = cur.fetchone()
        cur.close()

        if pdf_content:
            return send_file(io.BytesIO(pdf_content[0]), mimetype="application/pdf")
        else:
            return "No PDF available for this resume ID."
    except Exception as e:
        return f"An error occurred: {str(e)}"

@app.route("/", methods=["GET", "POST"])
def index():
    results = []
    if request.method == "POST":
        job_description = request.form["job_description"]
        job_description_text = f"job description: {job_description}"
        
        print("Job Description:", job_description_text)  # Log job description

        # Get roles and number of workers
        prompt_roles = """you are responsible of the HR department of a company you received the previous project description, based on it, you will generate a team with roles and the number of workers required for each role in the form of 'role:      '."""
        roles_text = get_gemini_response1(job_description_text, prompt_roles)
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
            keywords = get_gemini_response1(job_description_text, prompt_keywords)
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
                    })

            role_results = sorted(role_results, key=lambda x: -x['similarity'])[:number]
            results.append({ "role": role, "resumes": role_results })

    return render_template("index.php", results=results)

if __name__ == "__main__":
    app.run(debug=True)