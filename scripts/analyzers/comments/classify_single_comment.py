import joblib
import sys

# Load the trained pipeline
pipeline = joblib.load('./compound_classifier_pipeline.pkl')

# Get the input text from the command line
input_text = " ".join(sys.argv[1:])

# Predict the classification using the pipeline
predicted_label = pipeline.predict([input_text])[0]

print(f"Predicted label for input text '{input_text}': {predicted_label}")
