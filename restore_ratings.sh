#!/bin/bash

# Script to restore ratings from old database to current database

OLD_DB="/Users/silkedelvaux/Herd/Book-Elf/database/database.sqlite"
NEW_DB="/Users/silkedelvaux/Herd/Book-Elf-App-Laravel-Backend/database/database.sqlite"

echo "🔍 Checking databases..."
echo "Old database: $OLD_DB"
echo "New database: $NEW_DB"
echo ""

# Count ratings in old database
OLD_RATINGS=$(sqlite3 "$OLD_DB" "SELECT COUNT(*) FROM books WHERE rating IS NOT NULL AND rating > 0;")
echo "📊 Found $OLD_RATINGS books with ratings in old database"
echo ""

# Export ratings from old database
echo "📤 Exporting ratings from old database..."
sqlite3 "$OLD_DB" "SELECT title, author, rating FROM books WHERE rating IS NOT NULL AND rating > 0;" > /tmp/ratings_export.txt

# Import ratings into new database using title+author matching
echo "📥 Importing ratings into new database..."

UPDATED=0
SKIPPED=0

while IFS='|' read -r title author rating; do
    # Escape single quotes in title and author
    title_escaped=$(echo "$title" | sed "s/'/''/g")
    author_escaped=$(echo "$author" | sed "s/'/''/g")
    
    # Try to update the book by matching title and author
    RESULT=$(sqlite3 "$NEW_DB" "UPDATE books SET rating = $rating WHERE title = '$title_escaped' AND author = '$author_escaped' AND (rating IS NULL OR rating = 0); SELECT changes();")
    
    if [ "$RESULT" -eq "1" ]; then
        UPDATED=$((UPDATED + 1))
        echo "✅ Updated: $title by $author (rating: $rating)"
    else
        SKIPPED=$((SKIPPED + 1))
        echo "⏭️  Skipped: $title by $author (not found or already has rating)"
    fi
done < /tmp/ratings_export.txt

echo ""
echo "✨ Import complete!"
echo "   Updated: $UPDATED books"
echo "   Skipped: $SKIPPED books"
echo ""

# Verify
NEW_RATINGS=$(sqlite3 "$NEW_DB" "SELECT COUNT(*) FROM books WHERE rating IS NOT NULL AND rating > 0;")
echo "📊 Current database now has $NEW_RATINGS books with ratings"

# Cleanup
rm /tmp/ratings_export.txt
