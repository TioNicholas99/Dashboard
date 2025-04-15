import openai

def generate_questions(text, num_questions=5):
    prompt = f"""
    Buatkan {num_questions} soal ujian berdasarkan teks berikut:
    
    {text}
    
    Format soal:
    1. [Soal]
       a. [Pilihan A]
       b. [Pilihan B]
       c. [Pilihan C]
       d. [Pilihan D]
       Jawaban: [Huruf Jawaban Benar]
    """
    
    response = openai.ChatCompletion.create(
        model="gpt-3.5-turbo",
        messages=[{"role": "system", "content": "Anda adalah AI pembuat soal ujian."},
                  {"role": "user", "content": prompt}]
    )
    
    return response['choices'][0]['message']['content']

# Contoh penggunaan
bab_buku = """
Albert Einstein adalah seorang ilmuwan fisika teoretis yang terkenal dengan teori relativitasnya. 
Ia lahir di Jerman pada tahun 1879 dan memenangkan Hadiah Nobel Fisika pada tahun 1921 atas penjelasannya 
tentang efek fotolistrik, yang menjadi dasar perkembangan fisika kuantum.
"""

soal_ujian = generate_questions(bab_buku, num_questions=3)
print(soal_ujian)
