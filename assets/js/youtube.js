document.addEventListener("DOMContentLoaded", () => {
    // API Key provided by user
    const API_KEY = "AIzaSyA1bE6Qz4K6BQ_gZH-7jffko1npY9hsu4w";
    const youtubeSection = document.getElementById("youtube-section");
    const youtubeGrid = document.getElementById("youtube-grid");
    const loader = document.getElementById("youtube-loader");
    const errorContainer = document.getElementById("youtube-error");

    // Extract search query from URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const titre = urlParams.get("titre") ? urlParams.get("titre").trim() : "";
    const categories = urlParams.getAll("categorie[]");

    // Only search if there's a title or at least a category
    if (titre !== "" || categories.length > 0) {
        youtubeSection.style.display = "block";
        searchYouTube(titre, categories);
    }

    async function searchYouTube(titre, categories) {
        youtubeGrid.innerHTML = "";
        errorContainer.style.display = "none";
        loader.style.display = "block";

        try {
            // Build the query string
            let queryParts = [];
            if (titre) queryParts.push(titre);
            if (categories.length > 0) {
                queryParts.push(categories.join(" "));
            }
            
            // Add 'tutoriel' to focus search
            let finalQuery = queryParts.join(" ") + " tutoriel cours";

            const url = `https://www.googleapis.com/youtube/v3/search?part=snippet&q=${encodeURIComponent(finalQuery)}&type=video&maxResults=6&key=${API_KEY}&relevanceLanguage=fr`;
            
            const response = await fetch(url);
            
            if (!response.ok) {
                throw new Error("Erreur lors de la communication avec l'API YouTube.");
            }

            const data = await response.json();

            loader.style.display = "none";

            if (data.items && data.items.length > 0) {
                data.items.forEach(item => {
                    const card = createYouTubeCard(item);
                    youtubeGrid.appendChild(card);
                });
            } else {
                showError("Aucun tutoriel YouTube n'a été trouvé pour cette recherche.");
            }

        } catch (error) {
            loader.style.display = "none";
            showError("Impossible de charger les vidéos YouTube : " + error.message);
        }
    }

    function showError(message) {
        errorContainer.textContent = message;
        errorContainer.style.display = "block";
    }

    function createYouTubeCard(item) {
        const videoId = item.id.videoId;
        const snippet = item.snippet;
        const dateStr = new Date(snippet.publishedAt).toLocaleDateString('fr-FR');

        const card = document.createElement("div");
        card.className = "card";
        card.innerHTML = `
            <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 8px 8px 0 0;">
                <iframe src="https://www.youtube.com/embed/${videoId}" 
                        style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: 0;" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen></iframe>
            </div>
            <div class="card-body">
                <h3 class="card-title" style="font-size: 1rem; line-height: 1.4; margin-bottom: 0.5rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;" title="${snippet.title}">${escapeHTML(snippet.title)}</h3>
                <div class="card-meta">
                    <span style="color:var(--text-muted); font-size:0.85rem;">📺 ${escapeHTML(snippet.channelTitle)}</span>
                </div>
            </div>
            <div class="card-footer" style="padding-top: 0.5rem;">
                <button onclick="saveTutorial('${videoId}', '${escapeJsStr(snippet.title)}', '${escapeJsStr(snippet.channelTitle)}')" class="btn btn-sm btn-outline" style="width: 100%; justify-content: center;">
                    💾 Sauvegarder
                </button>
            </div>
        `;
        return card;
    }

    function escapeHTML(str) {
        let div = document.createElement('div');
        div.innerText = str;
        return div.innerHTML;
    }

    function escapeJsStr(str) {
        return str.replace(/'/g, "\\'").replace(/"/g, '&quot;');
    }
});

// Global function to save a tutorial to DB
async function saveTutorial(videoId, title, channelTitle) {
    try {
        const formData = new FormData();
        formData.append('action', 'save_youtube');
        formData.append('youtube_id', videoId);
        formData.append('titre', title);
        formData.append('nom_chaine', channelTitle);

        const response = await fetch('api/save_tutorial.php', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`HTTP Error: ${response.status}`);
        }

        const result = await response.json();
        
        if (result.success) {
            alert("Tutoriel sauvegardé avec succès dans votre espace !");
        } else {
            alert(result.message || "Erreur lors de la sauvegarde.");
        }
    } catch (e) {
        alert("Erreur réseau ou non connecté. Veuillez vous connecter pour sauvegarder.");
    }
}
