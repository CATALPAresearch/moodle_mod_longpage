from fastapi import FastAPI
import spacy
from fastapi.middleware.cors import CORSMiddleware

app = FastAPI()
origins = [
    "http://localhost",
]

app.add_middleware(
    CORSMiddleware,
    allow_origins=origins,
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

nlp = spacy.load('de_core_news_sm')


@app.post("/compare_similarity")
async def compare_similarity(text1: str, text2: str):
    sentences_txt1 = [sent.text for sent in nlp(text1).sents]
    doc2 = nlp(text2)
    similarities = []
    for sentence1 in sentences_txt1:
        for sentence2 in doc2.sents:
            similarity = nlp(sentence1).similarity(sentence2)
            start = text1.find(sentence1)
            similarities.append((start, len(sentence1), similarity))

    # get the three most similar sentences
    #similarities = sorted(similarities, key=lambda x: x[2], reverse=True)[:3]

    return similarities