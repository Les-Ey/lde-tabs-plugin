//
// LDE Tabs Plugin JavaScript Logic (jQuery Version) - Multi-Group Scoped
//

jQuery(document).ready(function($) {
    
    // Check if tabs exist before proceeding
    if ($('.lde-tab-button').length === 0) {
        return;
    }

    // --- 1. INITIAL VISIBILITY SETUP (FOUC FIX) ---
    // Hide ALL content on load, before the active tab is made visible. 
    // This is the safest way to ensure a clean slate, even across multiple groups.
    $('.lde-tab-content').css('display', 'none'); 

    // Iterate over every unique tab group on the page
    $('.lde-tab-group-wrapper').each(function() {
        var $groupWrapper = $(this);
        var $activeTabButton = $groupWrapper.find('.lde-tab-button.lde-active').first();
        
        if ($activeTabButton.length) {
            var activeTargetId = $activeTabButton.data('target');
            var $activeContent = $('#' + activeTargetId);

            // Apply the temporary class for instant visibility (FOUC fix)
            $activeContent.addClass('lde-tab-onloading lde-active');
            
            // Use a slight delay for smooth visual loading before removing the class
            setTimeout(function() {
                 $activeContent.removeClass('lde-tab-onloading');
                 
                 // Force display: block via inline style for runtime
                 $activeContent.css('display', 'block');
                 
            }, 50);
        }
    });


    // --- 2. CLICK HANDLER: Toggle active state and content visibility. ---
    $('.lde-tab-button').on('click', function() {
        var $clickedButton = $(this);
        var targetId = $clickedButton.data('target');
        var groupId = $clickedButton.data('group'); // Get the unique group ID
        
        var $groupWrapper = $('#lde-tabs-group-' + groupId);
        var $currentGroupButtons = $groupWrapper.find('.lde-tab-button');

        // Find ALL content blocks belonging to this specific group
        var groupContentIDs = $currentGroupButtons.map(function() {
            return '#' + $(this).data('target');
        }).get().join(',');
        
        // a. HIDE ALL CONTENT *IN THIS GROUP* AND REMOVE ALL ACTIVE CLASSES
        // We use the generated IDs string to hide content via inline style, 
        // which overrides the FOUC class and any conflicting theme styles.
        $(groupContentIDs).css('display', 'none').removeClass('lde-active'); // <--- CRITICAL FIX: Chained removeClass here.

        // b. CLEANUP CLASSES (Only within the current group buttons)
        $currentGroupButtons.removeClass('lde-active');

        // c. SHOW TARGET
        $('#' + targetId).css('display', 'block');
        
        // d. ADD ACTIVE CLASSES
        $clickedButton.addClass('lde-active');
        $('#' + targetId).addClass('lde-active');
    });
});