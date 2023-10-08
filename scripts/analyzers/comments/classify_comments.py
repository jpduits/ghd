import joblib
import sys
import csv
import os

# Load the trained pipeline
pipeline = joblib.load('compound_classifier_pipeline.pkl')

# Get the input CSV file from the command line
input_csv_file = sys.argv[1]

# Define the output CSV file name
output_csv_file = os.path.splitext(input_csv_file)[0] + '_classified.csv'

# Initialize dictionaries to store classifications and totals
classifications = {}
total_classifications = {}

# Open the input CSV file and output CSV file
with open(input_csv_file, 'r') as csvfile, open(output_csv_file, 'w', newline='') as outputfile:
    csvreader = csv.reader(csvfile)
    csvwriter = csv.writer(outputfile, delimiter=',', quotechar='"', quoting=csv.QUOTE_ALL)

    # Write the header for the output CSV
    csvwriter.writerow(['Type', 'Comment', 'Predicted Label'])

    # Process each row in the input CSV
    for row in csvreader:
        comment_type = row[0]
        input_text = row[1]

        # Predict the classification using the pipeline
        predicted_label = pipeline.predict([input_text])[0]

        # Add the predicted label to the row
        row.append(predicted_label)

        # Write the row to the output CSV
        csvwriter.writerow(row)

        # Update the dictionaries for classifications and totals
        if comment_type not in classifications:
            classifications[comment_type] = {}

        if predicted_label not in classifications[comment_type]:
            classifications[comment_type][predicted_label] = 0
        classifications[comment_type][predicted_label] += 1

        if predicted_label not in total_classifications:
            total_classifications[predicted_label] = 0
        total_classifications[predicted_label] += 1

# Print the total classifications
for label, count in total_classifications.items():
    print(f"{label},{count}")
