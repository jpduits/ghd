## How to use the machine learning scripts

### 1. Get comments for a single project
- Run from project root
- `scripts/analyzers/comments/get_comments_project.sh ~/tmp_checkouts/exchange-core`
- Results are saved to `processed/comments_<GIT_COMMIT_HASH>.csv

### 2. Get comments for multiple projects
- Add projects names to the `checkout_directories` array in `get_comments_projects.sh`
- Run from project root (the script executes `get_comments_project.sh` for each project.
- Results are saved to `processed/comments_<GIT_COMMIT_HASH>.csv

### 3. Training data
- Run the `get_comments_project.sh` script with`--train` flag
- Run from project root
- Comments will be added to the training data file `processed/comments_training_data.csv`
- Add a classification for each comment
- This file will be used as the train input file.

### 4. Train model with input file
- To train the model with the input file, run `python3 train_model.py`
- It generates a number of .pkl files
- Now you can use the model to classify comments

### 5. Use model to classify
- Run `python3 classify_comments.py <PROCESSED_COMMENTS_FILE>` (see section 1 and 2)
- The classified comments will be saved as `processed/comments_<GIT_COMMIT_HASH>_classified.csv`


