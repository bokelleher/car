// assets/js/church-directory.js

jQuery(document).ready(function($) {
    
    // Cache DOM elements
    const $searchInput = $('#car-church-search');
    const $churchCards = $('.car-church-card');
    const $churchCount = $('#church-count');
    const $noResults = $('.car-no-results');
    const $clearSearch = $('#clear-search');
    const $grid = $('.car-directory-grid');
    
    // Live search functionality
    $searchInput.on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        filterChurches(searchTerm);
    });
    
    // Clear search button
    $clearSearch.on('click', function() {
        $searchInput.val('');
        filterChurches('');
        $noResults.hide();
        $grid.show();
    });
    
    // Filter churches by search term
    function filterChurches(searchTerm) {
        let visibleCount = 0;
        
        $churchCards.each(function() {
            const $card = $(this);
            const name = $card.data('name') || '';
            const pastor = $card.data('pastor') || '';
            const city = $card.data('city') || '';
            
            // Search in name, pastor, and city
            const searchableText = name + ' ' + pastor + ' ' + city;
            
            if (searchTerm === '' || searchableText.includes(searchTerm)) {
                $card.removeClass('hidden');
                visibleCount++;
            } else {
                $card.addClass('hidden');
            }
        });
        
        updateCount(visibleCount);
        toggleNoResults(visibleCount === 0);
    }
    
    // Update church count
    function updateCount(count) {
        $churchCount.text(count);
    }
    
    // Toggle no results message
    function toggleNoResults(show) {
        if (show) {
            $noResults.show();
            $grid.hide();
        } else {
            $noResults.hide();
            $grid.show();
        }
    }
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Focus search on "/" key
        if (e.key === '/' && !$searchInput.is(':focus')) {
            e.preventDefault();
            $searchInput.focus();
        }
        
        // Clear search on ESC
        if (e.key === 'Escape' && $searchInput.is(':focus')) {
            $searchInput.val('').blur();
            filterChurches('');
        }
    });
    
    // Make phone numbers clickable on mobile
    if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
        $('.church-phone a').each(function() {
            const phone = $(this).text();
            const cleanPhone = phone.replace(/[^0-9]/g, '');
            $(this).attr('href', 'tel:' + cleanPhone);
        });
    }
    
    // Print functionality
    window.addEventListener('beforeprint', function() {
        // Show all churches when printing
        $churchCards.removeClass('hidden');
    });
    
    // Lazy loading for better performance with many churches
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const card = entry.target;
                    card.classList.add('loaded');
                    observer.unobserve(card);
                }
            });
        });
        
        document.querySelectorAll('.car-church-card').forEach(function(card) {
            imageObserver.observe(card);
        });
    }
    
    // Analytics tracking (if GA is present)
    if (typeof ga !== 'undefined') {
        // Track searches
        $searchInput.on('blur', function() {
            const searchTerm = $(this).val();
            if (searchTerm) {
                ga('send', 'event', 'Church Directory', 'search', searchTerm);
            }
        });
        
        // Track church website clicks
        $(document).on('click', '.btn-website', function() {
            const churchName = $(this).closest('.car-church-card').find('h3').text();
            ga('send', 'event', 'Church Directory', 'website_click', churchName);
        });
        
        // Track directions clicks
        $(document).on('click', '.btn-directions', function() {
            const churchName = $(this).closest('.car-church-card').find('h3').text();
            ga('send', 'event', 'Church Directory', 'directions_click', churchName);
        });
    }
    
    // Smooth scroll to church if coming from map
    if (window.location.hash) {
        const churchId = window.location.hash.substr(1);
        const $targetCard = $('[data-church-id="' + churchId + '"]');
        if ($targetCard.length) {
            $('html, body').animate({
                scrollTop: $targetCard.offset().top - 100
            }, 500);
            $targetCard.addClass('highlight');
            setTimeout(function() {
                $targetCard.removeClass('highlight');
            }, 2000);
        }
    }
});