import pandas as pd
from sklearn.pipeline import Pipeline, FeatureUnion
from sklearn.feature_extraction.text import CountVectorizer, TfidfVectorizer
from sklearn.model_selection import train_test_split
from sklearn.ensemble import RandomForestClassifier
import joblib


# Load the CSV file containing comments and labels
data = pd.read_csv("processed/_comments.csv")


data["comment"] = data["comment"].str.lower()

data["comment"].fillna("", inplace=True)

# Separate features (comments) and labels
X = data["comment"]
y = data["classification"]


# Separate features (comments) and labels
X = data["comment"]
y = data["classification"]

# Define a pipeline with both vectorizers and a classifier
"""
pipeline = Pipeline([
    ('vectorizer', TfidfVectorizer()),  # Use CountVectorizer or TfidfVectorizer here
    ('classifier', RandomForestClassifier())
])
 """

tfidf_vectorizer = TfidfVectorizer()
count_vectorizer = CountVectorizer()

# Definieer de pipeline met een FeatureUnion
pipeline = Pipeline([
    ('union', FeatureUnion([
        ('tfidf', tfidf_vectorizer),
        ('count', count_vectorizer)
    ])),
    ('classifier', RandomForestClassifier())
])



# Train-test split
X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42)

# Train the pipeline
pipeline.fit(X_train, y_train)

# Save the trained pipeline
joblib.dump(pipeline, './compound_classifier_pipeline.pkl')
print("Compound classifier pipeline is trained and saved.")
