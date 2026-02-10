document.addEventListener("DOMContentLoaded", function () {
    const searchBtn = document.getElementById("search-book");
    const searchAuthorBtn = document.getElementById("search-author");

    if (searchBtn) {
        searchBtn.addEventListener("click", async function () {
            const title = document.getElementById("title").value;

            if (!title) {
                alert("Voer eerst een titel in");
                return;
            }

            searchBtn.textContent = "Zoeken...";
            searchBtn.disabled = true;

            const language = document.getElementById("search-language").value;
            const langParam = language ? `&langRestrict=${language}` : "";

            try {
                // Haal alle beschikbare resultaten op
                const allBooks = await fetchAllBooks(`intitle:${encodeURIComponent(title)}`, langParam);

                if (allBooks.length > 0) {
                    if (allBooks.length === 1) {
                        // Slechts 1 boek gevonden, vul direct in
                        fillBookData(allBooks[0].volumeInfo);
                    } else {
                        // Meerdere boeken gevonden, laat gebruiker kiezen
                        showBookSelector(allBooks);
                    }
                } else {
                    alert("Geen boek gevonden met deze titel");
                }
            } catch (error) {
                alert("Er is iets misgegaan bij het zoeken");
                console.error(error);
            } finally {
                searchBtn.textContent = "Zoek";
                searchBtn.disabled = false;
            }
        });
    }
    
    if (searchAuthorBtn) {
        searchAuthorBtn.addEventListener("click", async function () {
            const author = document.getElementById("author").value;

            if (!author) {
                alert("Voer eerst een auteur in");
                return;
            }

            searchAuthorBtn.textContent = "Zoeken...";
            searchAuthorBtn.disabled = true;

            const language = document.getElementById("search-language").value;
            const langParam = language ? `&langRestrict=${language}` : "";

            try {
                // Haal alle beschikbare resultaten op
                const allBooks = await fetchAllBooks(`inauthor:${encodeURIComponent(author)}`, langParam);

                if (allBooks.length > 0) {
                    showBookSelector(allBooks, "Boeken van " + author);
                } else {
                    alert("Geen boeken gevonden van deze auteur");
                }
            } catch (error) {
                alert("Er is iets misgegaan bij het zoeken");
                console.error(error);
            } finally {
                searchAuthorBtn.textContent = "Zoek";
                searchAuthorBtn.disabled = false;
            }
        });
    }    
    const searchIsbnBtn = document.getElementById("search-isbn");
    
    if (searchIsbnBtn) {
        searchIsbnBtn.addEventListener("click", async function () {
            const isbn = document.getElementById("isbn").value;

            if (!isbn) {
                alert("Voer eerst een ISBN in");
                return;
            }

            searchIsbnBtn.textContent = "Zoeken...";
            searchIsbnBtn.disabled = true;

            try {
                const response = await fetch(
                    `https://www.googleapis.com/books/v1/volumes?q=isbn:${encodeURIComponent(isbn)}`,
                );
                const data = await response.json();

                if (data.items && data.items.length > 0) {
                    fillBookData(data.items[0].volumeInfo);
                } else {
                    alert("Geen boek gevonden met dit ISBN nummer");
                }
            } catch (error) {
                alert("Er is iets misgegaan bij het zoeken");
                console.error(error);
            } finally {
                searchIsbnBtn.textContent = "Zoek";
                searchIsbnBtn.disabled = false;
            }
        });
    }
});

async function fetchAllBooks(query, langParam, maxTotal = 100) {
    let allBooks = [];
    const maxResults = 40; // Google Books API limiet per request
    let startIndex = 0;
    
    try {
        // Eerste request om totaal te krijgen
        const firstResponse = await fetch(
            `https://www.googleapis.com/books/v1/volumes?q=${query}&maxResults=${maxResults}${langParam}&startIndex=${startIndex}`
        );
        const firstData = await firstResponse.json();
        
        if (!firstData.items) {
            return [];
        }
        
        allBooks = firstData.items;
        const totalItems = Math.min(firstData.totalItems, maxTotal); // Limiteer tot maxTotal
        
        // Haal rest op in batches
        while (allBooks.length < totalItems) {
            startIndex += maxResults;
            const response = await fetch(
                `https://www.googleapis.com/books/v1/volumes?q=${query}&maxResults=${maxResults}${langParam}&startIndex=${startIndex}`
            );
            const data = await response.json();
            
            if (data.items) {
                allBooks = allBooks.concat(data.items);
            } else {
                break;
            }
        }
        
        return allBooks.slice(0, maxTotal);
    } catch (error) {
        console.error("Fout bij ophalen boeken:", error);
        return allBooks;
    }
}

function fillBookData(book) {
    // Vul titel in
    if (book.title) {
        document.getElementById("title").value = book.title;
    }
    
    // Vul auteur in
    if (book.authors && book.authors.length > 0) {
        document.getElementById("author").value = book.authors.join(", ");
    }

    // Vul ISBN in
    if (book.industryIdentifiers && book.industryIdentifiers.length > 0) {
        // Kies voorkeur: ISBN_13 > ISBN_10
        const isbn = book.industryIdentifiers.find(id => id.type === "ISBN_13") 
                  || book.industryIdentifiers[0];
        document.getElementById("isbn").value = isbn.identifier;
    }
    
    alert("Boekgegevens ingevuld!");
}

function showBookSelector(books, title = "Meerdere resultaten gevonden - kies het juiste boek:") {
    const modal = document.createElement('div');
    modal.id = 'isbn-modal';
    modal.innerHTML = `
        <div class="modal-content">
            <h3>${title}</h3>
            <div class="isbn-list">
                ${books.map((item, index) => {
                    const book = item.volumeInfo;
                    const author = book.authors ? book.authors.join(", ") : "Onbekende auteur";
                    const year = book.publishedDate ? ` (${book.publishedDate.substring(0, 4)})` : "";
                    return `
                        <button class="isbn-option" data-index="${index}">
                            <strong>${book.title}</strong><br>
                            ${author}${year}
                        </button>
                    `;
                }).join('')}
            </div>
            <button class="modal-close">Annuleren</button>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    modal.querySelectorAll('.isbn-option').forEach(btn => {
        btn.addEventListener('click', function() {
            const index = parseInt(this.dataset.index);
            fillBookData(books[index].volumeInfo);
            document.body.removeChild(modal);
        });
    });
    
    modal.querySelector('.modal-close').addEventListener('click', function() {
        document.body.removeChild(modal);
    });
}

