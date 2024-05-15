from fastapi import FastAPI
import spacy
from fastapi.middleware.cors import CORSMiddleware
from fuzzywuzzy import fuzz
from llama_index.core import Document, VectorStoreIndex
from llama_index.llms.ollama import Ollama
from llama_index.core import StorageContext
from llama_index.embeddings.huggingface import HuggingFaceEmbedding
import chromadb
from llama_index.vector_stores.chroma import ChromaVectorStore
from llama_index.core import VectorStoreIndex, get_response_synthesizer
from llama_index.core.retrievers import VectorIndexRetriever
from llama_index.core.query_engine import RetrieverQueryEngine
from llama_index.core.postprocessor import SimilarityPostprocessor

llm = Ollama(model="mixtral", base_url="http://132.176.10.80", prompt_key="sk-Dl9C3sCqc5UEmb8dTMLL8g")
chroma_client = chromadb.EphemeralClient()
chroma_collection = chroma_client.create_collection("iollama")

vector_store = ChromaVectorStore(chroma_collection=chroma_collection)
storage_context = StorageContext.from_defaults(vector_store=vector_store)
embed_model = HuggingFaceEmbedding(model_name="BAAI/bge-small-en-v1.5")

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

# nlp_sent = spacy.load('de_core_news_sm')
# nlp_sim = spacy.load('de_core_news_lg')
response_synthesizer = get_response_synthesizer(llm=llm)
postprocessors = [SimilarityPostprocessor(similarity_cutoff=0.55)]

@app.post("/compare_similarity")
async def compare_similarity(text1: str, text2: str):
    documents = []
    index = 0
    for t in text1.split("."):
        if t.strip() == "":
            continue
        documents.append(Document(text=t, extra_info={"index": index, "length": len(t)+1}))
        index += len(t)+1
   
    index = VectorStoreIndex.from_documents(documents, storage_context=storage_context, embed_model=embed_model)
    retriever = VectorIndexRetriever(index=index, similarity_top_k=len(documents))
    
    query_engine = RetrieverQueryEngine(
        retriever=retriever,
        response_synthesizer=response_synthesizer,
        node_postprocessors=postprocessors,
    )
    #query_engine = index.as_query_engine(llm=llm)
    response = query_engine.query(text2)

    similarities = []
    if response is None or response.metadata is None:
        return similarities

    for meta in response.metadata:
        similarities.append((response.metadata[meta]["index"], response.metadata[meta]["length"]))

    for i, node in enumerate(response.source_nodes):
        similarities[i] = (similarities[i][0], similarities[i][1], node.score)

    return similarities

    # doc1 = nlp_sim(text1)
    # doc2 = nlp_sim(text2)
    # similarities = []
    # for sentence1 in doc1.sents:
    #     similarity1 = sentence1.similarity(doc2)
    #     similarity2 = fuzz.token_set_ratio(sentence1.text, text2) / 100
    #     start = text1.find(sentence1.text)
    #     similarities.append((start, len(sentence1.text), 2*similarity1*similarity2))

    # # get the three most similar sentences
    # #similarities = sorted(similarities, key=lambda x: x[2], reverse=True)[:3]

    # return similarities
    