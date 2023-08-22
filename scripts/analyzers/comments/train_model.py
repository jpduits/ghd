import pandas as pd
from sklearn.feature_extraction.text import CountVectorizer
from sklearn.model_selection import train_test_split
from sklearn.naive_bayes import MultinomialNB
import joblib

# Load the CSV file containing comments and labels
data = pd.read_csv("./processed/comments.csv")

# Separate features (comments) and labels
X = data["comment"]
y = data["classification"]

# Convert text to numerical features
vectorizer = CountVectorizer()
X_vectorized = vectorizer.fit_transform(X)

# Train-test split
X_train, X_test, y_train, y_test = train_test_split(X_vectorized, y, test_size=0.2, random_state=42)

# Choose and train a model
model = MultinomialNB()
model.fit(X_train, y_train)

# Save the model
joblib.dump(model, 'comments_model.pkl')
print("Model is trained and saved.")
