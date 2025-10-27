<?php
/**
 * Dasher Document Manager
 * 
 * Handles document conversion and access control for BigBird and Mentor users
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Dasher_Document_Manager {
    
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // Create custom post type for documents
        add_action('init', array($this, 'register_document_post_type'));
        
        // Create document library pages
        add_action('init', array($this, 'create_document_library_pages'));
        
        // Add AJAX handlers
        add_action('wp_ajax_dasher_create_document_page', array($this, 'ajax_create_document_page'));
        add_action('wp_ajax_dasher_get_documents', array($this, 'ajax_get_documents'));
        add_action('wp_ajax_dasher_scan_documents', array($this, 'ajax_scan_documents'));
        
        // Add document access cards to dashboards
        add_filter('dasher_mentor_dashboard_cards', array($this, 'add_mentor_document_cards'));
        add_filter('dasher_big_bird_dashboard_cards', array($this, 'add_bigbird_document_cards'));
        
        // Add admin menu items
        add_action('admin_menu', array($this, 'add_admin_menu_items'), 30);
        
        // Add shortcodes
        add_shortcode('dasher_document_library', array($this, 'render_document_library'));
        add_shortcode('dasher_document_viewer', array($this, 'render_document_viewer'));
    }
    
    /**
     * Register custom post type for documents
     */
    public function register_document_post_type() {
        $args = array(
            'labels' => array(
                'name' => 'Dasher Documents',
                'singular_name' => 'Document',
                'add_new' => 'Add New Document',
                'add_new_item' => 'Add New Document',
                'edit_item' => 'Edit Document',
                'new_item' => 'New Document',
                'view_item' => 'View Document',
                'search_items' => 'Search Documents',
                'not_found' => 'No documents found',
                'not_found_in_trash' => 'No documents found in trash'
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'dasher-settings',
            'capability_type' => 'post',
            'capabilities' => array(
                'edit_post' => 'dasher_mentor',
                'read_post' => 'dasher_mentor',
                'delete_post' => 'dasher_mentor',
                'edit_posts' => 'dasher_mentor',
                'edit_others_posts' => 'dasher_mentor',
                'publish_posts' => 'dasher_mentor',
                'read_private_posts' => 'dasher_mentor'
            ),
            'supports' => array('title', 'editor', 'custom-fields'),
            'has_archive' => false,
            'rewrite' => array('slug' => 'dasher-docs'),
            'meta_box_cb' => array($this, 'add_document_meta_boxes')
        );
        
        register_post_type('dasher_document', $args);
    }
    
    /**
     * Add custom meta boxes for documents
     */
    public function add_document_meta_boxes() {
        add_meta_box(
            'dasher_document_access',
            'Document Access Control',
            array($this, 'render_access_meta_box'),
            'dasher_document',
            'side',
            'high'
        );
        
        add_meta_box(
            'dasher_document_source',
            'Source Document Info',
            array($this, 'render_source_meta_box'),
            'dasher_document',
            'side',
            'default'
        );
    }
    
    /**
     * Render access control meta box
     */
    public function render_access_meta_box($post) {
        wp_nonce_field('dasher_document_meta', 'dasher_document_nonce');
        
        $access_roles = get_post_meta($post->ID, '_dasher_access_roles', true);
        $document_category = get_post_meta($post->ID, '_dasher_document_category', true);
        $is_printable = get_post_meta($post->ID, '_dasher_is_printable', true);
        
        if (!is_array($access_roles)) {
            $access_roles = array();
        }
        ?>
        <p>
            <label><strong>Access Roles:</strong></label><br>
            <label><input type="checkbox" name="dasher_access_roles[]" value="dasher_mentor" <?php checked(in_array('dasher_mentor', $access_roles)); ?>> Mentors</label><br>
            <label><input type="checkbox" name="dasher_access_roles[]" value="dasher_bigbird" <?php checked(in_array('dasher_bigbird', $access_roles)); ?>> BigBirds</label><br>
            <label><input type="checkbox" name="dasher_access_roles[]" value="dasher_pc" <?php checked(in_array('dasher_pc', $access_roles)); ?>> Program Candidates</label>
        </p>
        
        <p>
            <label for="dasher_document_category"><strong>Category:</strong></label>
            <select name="dasher_document_category" id="dasher_document_category" style="width: 100%;">
                <option value="">Select Category</option>
                <option value="guidelines" <?php selected($document_category, 'guidelines'); ?>>Guidelines</option>
                <option value="evaluations" <?php selected($document_category, 'evaluations'); ?>>Evaluations</option>
                <option value="outlines" <?php selected($document_category, 'outlines'); ?>>Group Outlines</option>
                <option value="exams" <?php selected($document_category, 'exams'); ?>>Exams</option>
                <option value="templates" <?php selected($document_category, 'templates'); ?>>Templates</option>
                <option value="training" <?php selected($document_category, 'training'); ?>>Training Materials</option>
                <option value="reference" <?php selected($document_category, 'reference'); ?>>Reference</option>
            </select>
        </p>
        
        <p>
            <label>
                <input type="checkbox" name="dasher_is_printable" value="1" <?php checked($is_printable, '1'); ?>>
                <strong>Printable Document</strong>
            </label>
        </p>
        <?php
    }
    
    /**
     * Render source document meta box
     */
    public function render_source_meta_box($post) {
        $source_file = get_post_meta($post->ID, '_dasher_source_file', true);
        $source_path = get_post_meta($post->ID, '_dasher_source_path', true);
        $conversion_date = get_post_meta($post->ID, '_dasher_conversion_date', true);
        ?>
        <p>
            <label for="dasher_source_file"><strong>Source File:</strong></label>
            <input type="text" name="dasher_source_file" id="dasher_source_file" value="<?php echo esc_attr($source_file); ?>" style="width: 100%;" readonly>
        </p>
        
        <p>
            <label for="dasher_source_path"><strong>Original Path:</strong></label>
            <textarea name="dasher_source_path" id="dasher_source_path" style="width: 100%; height: 60px;" readonly><?php echo esc_textarea($source_path); ?></textarea>
        </p>
        
        <?php if ($conversion_date): ?>
        <p>
            <strong>Converted:</strong> <?php echo date('M j, Y g:i A', strtotime($conversion_date)); ?>
        </p>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Save document meta data
     */
    public function save_document_meta($post_id) {
        if (!isset($_POST['dasher_document_nonce']) || !wp_verify_nonce($_POST['dasher_document_nonce'], 'dasher_document_meta')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save access roles
        $access_roles = isset($_POST['dasher_access_roles']) ? $_POST['dasher_access_roles'] : array();
        update_post_meta($post_id, '_dasher_access_roles', $access_roles);
        
        // Save document category
        if (isset($_POST['dasher_document_category'])) {
            update_post_meta($post_id, '_dasher_document_category', sanitize_text_field($_POST['dasher_document_category']));
        }
        
        // Save printable flag
        $is_printable = isset($_POST['dasher_is_printable']) ? '1' : '0';
        update_post_meta($post_id, '_dasher_is_printable', $is_printable);
        
        // Save source info if provided
        if (isset($_POST['dasher_source_file'])) {
            update_post_meta($post_id, '_dasher_source_file', sanitize_text_field($_POST['dasher_source_file']));
        }
        
        if (isset($_POST['dasher_source_path'])) {
            update_post_meta($post_id, '_dasher_source_path', sanitize_textarea_field($_POST['dasher_source_path']));
        }
    }
    
    /**
     * Add document cards to Mentor dashboard
     */
    public function add_mentor_document_cards($cards) {
        $mentor_docs = $this->get_documents_for_role('dasher_mentor');
        
        $cards[] = array(
            'title' => 'Mentor Resources',
            'icon' => 'dashicons-media-document',
            'class' => 'info',
            'content' => $this->render_document_card($mentor_docs, 'mentor')
        );
        
        return $cards;
    }
    
    /**
     * Add document cards to BigBird dashboard
     */
    public function add_bigbird_document_cards($cards) {
        $bigbird_docs = $this->get_documents_for_role('dasher_bigbird');
        
        $cards[] = array(
            'title' => 'BigBird Resources',
            'icon' => 'dashicons-media-document',
            'class' => 'success',
            'content' => $this->render_document_card($bigbird_docs, 'bigbird')
        );
        
        return $cards;
    }
    
    
    /**
     * Render document card content
     */
    private function render_document_card($documents, $role) {
        ob_start();
        ?>
        <div class="dasher-card-value"><?php echo count($documents); ?></div>
        <div class="dasher-card-description">Available documents and resources</div>
        
        <?php if (!empty($documents)): ?>
            <div class="document-quick-list">
                <?php 
                $categories = array();
                foreach ($documents as $doc) {
                    $category = get_post_meta($doc->ID, '_dasher_document_category', true);
                    if (!$category) $category = 'other';
                    
                    if (!isset($categories[$category])) {
                        $categories[$category] = 0;
                    }
                    $categories[$category]++;
                }
                ?>
                
                <div class="category-breakdown">
                    <?php foreach ($categories as $category => $count): ?>
                        <div class="category-item">
                            <span class="category-name"><?php echo ucfirst($category); ?>:</span>
                            <span class="category-count"><?php echo $count; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <p>No documents available yet.</p>
        <?php endif; ?>
        
        <div class="dasher-card-actions">
            <a href="<?php echo admin_url('edit.php?post_type=dasher_document'); ?>" class="dasher-btn secondary">
                View All Documents
            </a>
            <a href="<?php echo admin_url('admin.php?page=dasher-document-converter'); ?>" class="dasher-btn primary">
                Convert Documents
            </a>
        </div>
        
        <style>
        .category-breakdown {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin: 12px 0;
        }
        
        .category-item {
            display: flex;
            justify-content: space-between;
            padding: 4px 8px;
            background: rgba(255,255,255,0.5);
            border-radius: 4px;
            font-size: 12px;
        }
        
        .category-name {
            font-weight: 500;
        }
        
        .category-count {
            color: #666;
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Add admin menu items
     */
    public function add_admin_menu_items() {
        add_submenu_page(
            'dasher-settings',
            'Document Converter',
            'Document Converter',
            'dasher_mentor',
            'dasher-document-converter',
            array($this, 'render_document_converter_page')
        );
    }
    
    /**
     * Render document converter admin page
     */
    public function render_document_converter_page() {
        ?>
        <div class="wrap">
            <h1>Document Converter</h1>
            <p>Convert uploaded documents to web pages with proper access control.</p>
            
            <div class="document-converter-interface">
                <div class="converter-section">
                    <h2>BigBird Documents</h2>
                    <div id="bigbird-documents">
                        <button class="button button-primary" onclick="loadDocuments('bigbird')">
                            Scan BigBird Documents
                        </button>
                        <div id="bigbird-doc-list"></div>
                    </div>
                </div>
                
                <div class="converter-section">
                    <h2>Mentor Documents</h2>
                    <div id="mentor-documents">
                        <button class="button button-primary" onclick="loadDocuments('mentor')">
                            Scan Mentor Documents
                        </button>
                        <div id="mentor-doc-list"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        function loadDocuments(type) {
            const button = document.querySelector(`#${type}-documents .button`);
            const listContainer = document.querySelector(`#${type}-doc-list`);
            
            button.textContent = 'Scanning...';
            button.disabled = true;
            
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'dasher_scan_documents',
                    type: type,
                    nonce: '<?php echo wp_create_nonce('dasher_admin_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        listContainer.innerHTML = response.data.html;
                    } else {
                        listContainer.innerHTML = '<p>Error: ' + response.data.message + '</p>';
                    }
                },
                error: function() {
                    listContainer.innerHTML = '<p>Connection error. Please try again.</p>';
                },
                complete: function() {
                    button.textContent = type === 'bigbird' ? 'Scan BigBird Documents' : 'Scan Mentor Documents';
                    button.disabled = false;
                }
            });
        }
        
        function convertDocument(filePath, fileName, category, roles) {
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'dasher_create_document_page',
                    file_path: filePath,
                    file_name: fileName,
                    category: category,
                    roles: roles,
                    nonce: '<?php echo wp_create_nonce('dasher_admin_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        alert('Document converted successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('Connection error. Please try again.');
                }
            });
        }
        </script>
        
        <style>
        .document-converter-interface {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }
        
        .converter-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .converter-section h2 {
            margin-top: 0;
            color: #2c3e50;
        }
        
        @media (max-width: 768px) {
            .document-converter-interface {
                grid-template-columns: 1fr;
            }
        }
        </style>
        <?php
    }
    
    /**
     * AJAX: Scan documents in upload directories
     */
    public function ajax_scan_documents() {
        check_ajax_referer('dasher_admin_nonce', 'nonce');
        
        if (!current_user_can('dasher_mentor')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Access denied')));
        }
        
        $type = sanitize_text_field($_POST['type']);
        $upload_dir = wp_upload_dir();
        
        if ($type === 'bigbird') {
            $scan_path = $upload_dir['basedir'] . '/LCCP | Big Bird/';
        } elseif ($type === 'mentor') {
            $scan_path = $upload_dir['basedir'] . '/LCCP | Mentor Documents/';
        } else {
            wp_die(json_encode(array('success' => false, 'message' => 'Invalid document type')));
        }
        
        if (!is_dir($scan_path)) {
            wp_die(json_encode(array('success' => false, 'message' => 'Document directory not found')));
        }
        
        $documents = $this->scan_directory($scan_path);
        $html = $this->render_document_scan_results($documents, $type);
        
        wp_die(json_encode(array(
            'success' => true,
            'data' => array('html' => $html)
        )));
    }
    
    /**
     * Recursively scan directory for documents
     */
    private function scan_directory($path, $relative_path = '') {
        $documents = array();
        
        if (!is_dir($path)) {
            return $documents;
        }
        
        $files = scandir($path);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $full_path = $path . $file;
            $relative_file_path = $relative_path . $file;
            
            if (is_dir($full_path)) {
                // Recursively scan subdirectories
                $subdocs = $this->scan_directory($full_path . '/', $relative_file_path . '/');
                $documents = array_merge($documents, $subdocs);
            } else {
                // Check if it's a supported document type
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($ext, array('pdf', 'doc', 'docx', 'txt', 'xlsx', 'xls'))) {
                    $documents[] = array(
                        'name' => $file,
                        'path' => $full_path,
                        'relative_path' => $relative_file_path,
                        'ext' => $ext,
                        'size' => filesize($full_path),
                        'modified' => filemtime($full_path),
                        'category' => $this->guess_document_category($file, $relative_file_path)
                    );
                }
            }
        }
        
        return $documents;
    }
    
    /**
     * Guess document category based on filename and path
     */
    private function guess_document_category($filename, $path) {
        $filename_lower = strtolower($filename);
        $path_lower = strtolower($path);
        
        // Category mapping
        $categories = array(
            'guidelines' => array('guideline', 'rule', 'overview', 'general'),
            'evaluations' => array('evaluation', 'assessment', 'rating', 'review'),
            'outlines' => array('outline', 'group', 'session', 'month'),
            'exams' => array('exam', 'test', 'final', 'phase'),
            'templates' => array('template', 'form', 'agreement', 'release'),
            'training' => array('training', 'audio', 'video', 'mp3', 'mp4'),
            'reference' => array('list', 'document', 'resource')
        );
        
        foreach ($categories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($filename_lower, $keyword) !== false || strpos($path_lower, $keyword) !== false) {
                    return $category;
                }
            }
        }
        
        return 'reference'; // Default category
    }
    
    /**
     * Render document scan results
     */
    private function render_document_scan_results($documents, $type) {
        if (empty($documents)) {
            return '<p>No documents found in the ' . ucfirst($type) . ' directory.</p>';
        }
        
        ob_start();
        ?>
        <div class="document-scan-results">
            <h3>Found <?php echo count($documents); ?> documents</h3>
            
            <div class="document-list">
                <?php foreach ($documents as $doc): ?>
                    <div class="document-item">
                        <div class="document-info">
                            <strong><?php echo esc_html($doc['name']); ?></strong>
                            <div class="document-meta">
                                <span class="file-size"><?php echo size_format($doc['size']); ?></span>
                                <span class="file-type"><?php echo strtoupper($doc['ext']); ?></span>
                                <span class="file-date"><?php echo date('M j, Y', $doc['modified']); ?></span>
                            </div>
                            <div class="document-path"><?php echo esc_html($doc['relative_path']); ?></div>
                        </div>
                        
                        <div class="document-actions">
                            <select class="category-select" id="category-<?php echo md5($doc['path']); ?>">
                                <option value="">Select Category</option>
                                <option value="guidelines" <?php selected($doc['category'], 'guidelines'); ?>>Guidelines</option>
                                <option value="evaluations" <?php selected($doc['category'], 'evaluations'); ?>>Evaluations</option>
                                <option value="outlines" <?php selected($doc['category'], 'outlines'); ?>>Group Outlines</option>
                                <option value="exams" <?php selected($doc['category'], 'exams'); ?>>Exams</option>
                                <option value="templates" <?php selected($doc['category'], 'templates'); ?>>Templates</option>
                                <option value="training" <?php selected($doc['category'], 'training'); ?>>Training Materials</option>
                                <option value="reference" <?php selected($doc['category'], 'reference'); ?>>Reference</option>
                            </select>
                            
                            <button class="button button-primary" 
                                    onclick="convertDocument(
                                        '<?php echo esc_js($doc['path']); ?>', 
                                        '<?php echo esc_js($doc['name']); ?>', 
                                        document.getElementById('category-<?php echo md5($doc['path']); ?>').value,
                                        '<?php echo $type === 'bigbird' ? 'dasher_bigbird' : 'dasher_mentor'; ?>'
                                    )">
                                Convert to Page
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <style>
        .document-scan-results {
            margin-top: 20px;
        }
        
        .document-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .document-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 6px;
            border-left: 4px solid #0073aa;
        }
        
        .document-info {
            flex: 1;
        }
        
        .document-meta {
            display: flex;
            gap: 15px;
            margin: 5px 0;
            font-size: 12px;
            color: #666;
        }
        
        .document-path {
            font-size: 11px;
            color: #999;
            font-family: monospace;
        }
        
        .document-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .category-select {
            min-width: 150px;
        }
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX: Create document page from uploaded file
     */
    public function ajax_create_document_page() {
        check_ajax_referer('dasher_admin_nonce', 'nonce');
        
        if (!current_user_can('dasher_mentor')) {
            wp_die(json_encode(array('success' => false, 'message' => 'Access denied')));
        }
        
        $file_path = sanitize_text_field($_POST['file_path']);
        $file_name = sanitize_text_field($_POST['file_name']);
        $category = sanitize_text_field($_POST['category']);
        $roles = sanitize_text_field($_POST['roles']);
        
        if (!file_exists($file_path)) {
            wp_die(json_encode(array('success' => false, 'message' => 'Source file not found')));
        }
        
        // Extract content based on file type
        $content = $this->extract_document_content($file_path);
        
        if (!$content) {
            wp_die(json_encode(array('success' => false, 'message' => 'Could not extract document content')));
        }
        
        // Create WordPress page
        $post_data = array(
            'post_title' => pathinfo($file_name, PATHINFO_FILENAME),
            'post_content' => $content,
            'post_status' => 'publish',
            'post_type' => 'dasher_document'
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            wp_die(json_encode(array('success' => false, 'message' => 'Failed to create page')));
        }
        
        // Save meta data
        update_post_meta($post_id, '_dasher_access_roles', array($roles));
        update_post_meta($post_id, '_dasher_document_category', $category);
        update_post_meta($post_id, '_dasher_source_file', $file_name);
        update_post_meta($post_id, '_dasher_source_path', $file_path);
        update_post_meta($post_id, '_dasher_conversion_date', current_time('mysql'));
        update_post_meta($post_id, '_dasher_is_printable', '1');
        
        wp_die(json_encode(array(
            'success' => true,
            'message' => 'Document converted successfully',
            'post_id' => $post_id
        )));
    }
    
    /**
     * Extract content from various document types
     */
    private function extract_document_content($file_path) {
        $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        
        switch ($ext) {
            case 'txt':
                return $this->extract_text_content($file_path);
            case 'pdf':
                return $this->extract_pdf_placeholder($file_path);
            case 'doc':
            case 'docx':
                return $this->extract_doc_placeholder($file_path);
            case 'xls':
            case 'xlsx':
                return $this->extract_excel_placeholder($file_path);
            default:
                return false;
        }
    }
    
    /**
     * Extract plain text content
     */
    private function extract_text_content($file_path) {
        $content = file_get_contents($file_path);
        return wpautop(esc_html($content));
    }
    
    /**
     * Create placeholder for PDF documents
     */
    private function extract_pdf_placeholder($file_path) {
        $file_name = basename($file_path);
        $file_size = size_format(filesize($file_path));
        $upload_url = wp_upload_dir()['baseurl'];
        $relative_path = str_replace(wp_upload_dir()['basedir'], '', $file_path);
        $download_url = $upload_url . $relative_path;
        
        return sprintf(
            '<div class="dasher-document-viewer pdf-document">
                <div class="document-header">
                    <h3>%s</h3>
                    <div class="document-meta">
                        <span class="file-type">PDF Document</span>
                        <span class="file-size">%s</span>
                    </div>
                </div>
                
                <div class="document-actions">
                    <a href="%s" class="button button-primary" download>
                        <span class="dashicons dashicons-download"></span> Download PDF
                    </a>
                    <a href="%s" class="button button-secondary" target="_blank">
                        <span class="dashicons dashicons-external"></span> View in New Tab
                    </a>
                </div>
                
                <div class="document-embed">
                    <iframe src="%s" width="100%%" height="800px" style="border: 1px solid #ddd; border-radius: 4px;">
                        <p>Your browser does not support iframes. <a href="%s">Download the PDF</a> instead.</p>
                    </iframe>
                </div>
            </div>
            
            <style>
            .dasher-document-viewer {
                background: white;
                border-radius: 8px;
                padding: 20px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            
            .document-header {
                margin-bottom: 20px;
                padding-bottom: 15px;
                border-bottom: 1px solid #eee;
            }
            
            .document-header h3 {
                margin: 0 0 10px 0;
                color: #2c3e50;
            }
            
            .document-meta {
                display: flex;
                gap: 15px;
                font-size: 14px;
                color: #666;
            }
            
            .document-actions {
                display: flex;
                gap: 10px;
                margin-bottom: 20px;
            }
            
            .document-actions .button {
                display: inline-flex;
                align-items: center;
                gap: 5px;
            }
            
            .document-embed {
                margin-top: 20px;
            }
            
            @media print {
                .document-actions {
                    display: none;
                }
            }
            </style>',
            esc_html($file_name),
            $file_size,
            esc_url($download_url),
            esc_url($download_url),
            esc_url($download_url),
            esc_url($download_url)
        );
    }
    
    /**
     * Create placeholder for Word documents
     */
    private function extract_doc_placeholder($file_path) {
        $file_name = basename($file_path);
        $file_size = size_format(filesize($file_path));
        $upload_url = wp_upload_dir()['baseurl'];
        $relative_path = str_replace(wp_upload_dir()['basedir'], '', $file_path);
        $download_url = $upload_url . $relative_path;
        
        return sprintf(
            '<div class="dasher-document-viewer word-document">
                <div class="document-header">
                    <h3>%s</h3>
                    <div class="document-meta">
                        <span class="file-type">Word Document</span>
                        <span class="file-size">%s</span>
                    </div>
                </div>
                
                <div class="document-actions">
                    <a href="%s" class="button button-primary" download>
                        <span class="dashicons dashicons-download"></span> Download Document
                    </a>
                </div>
                
                <div class="document-placeholder">
                    <div class="placeholder-icon">
                        <span class="dashicons dashicons-media-document"></span>
                    </div>
                    <p>This is a Word document. Click the download button above to access the full content.</p>
                    <p><strong>Note:</strong> You will need Microsoft Word or a compatible application to view this document.</p>
                </div>
            </div>',
            esc_html($file_name),
            $file_size,
            esc_url($download_url)
        );
    }
    
    /**
     * Create placeholder for Excel documents
     */
    private function extract_excel_placeholder($file_path) {
        $file_name = basename($file_path);
        $file_size = size_format(filesize($file_path));
        $upload_url = wp_upload_dir()['baseurl'];
        $relative_path = str_replace(wp_upload_dir()['basedir'], '', $file_path);
        $download_url = $upload_url . $relative_path;
        
        return sprintf(
            '<div class="dasher-document-viewer excel-document">
                <div class="document-header">
                    <h3>%s</h3>
                    <div class="document-meta">
                        <span class="file-type">Excel Spreadsheet</span>
                        <span class="file-size">%s</span>
                    </div>
                </div>
                
                <div class="document-actions">
                    <a href="%s" class="button button-primary" download>
                        <span class="dashicons dashicons-download"></span> Download Spreadsheet
                    </a>
                </div>
                
                <div class="document-placeholder">
                    <div class="placeholder-icon">
                        <span class="dashicons dashicons-media-spreadsheet"></span>
                    </div>
                    <p>This is an Excel spreadsheet. Click the download button above to access the full content.</p>
                    <p><strong>Note:</strong> You will need Microsoft Excel or a compatible spreadsheet application to view this document.</p>
                </div>
            </div>',
            esc_html($file_name),
            $file_size,
            esc_url($download_url)
        );
    }
    
    /**
     * Render document library shortcode
     */
    public function render_document_library($atts) {
        $atts = shortcode_atts(array(
            'role' => '',
            'category' => '',
            'limit' => -1,
            'view' => 'grid'
        ), $atts);
        
        // Get current user role if not specified
        if (empty($atts['role'])) {
            $current_user = wp_get_current_user();
            if (in_array('dasher_mentor', $current_user->roles)) {
                $atts['role'] = 'dasher_mentor';
            } elseif (in_array('dasher_bigbird', $current_user->roles)) {
                $atts['role'] = 'dasher_bigbird';
            } elseif (in_array('dasher_pc', $current_user->roles)) {
                $atts['role'] = 'dasher_pc';
            }
        }
        
        if (empty($atts['role'])) {
            return '<p>Access denied. Please log in with appropriate permissions.</p>';
        }
        
        $documents = $this->get_documents_for_role($atts['role'], $atts['category'], $atts['limit']);
        
        ob_start();
        ?>
        <div class="dasher-document-library" data-view="<?php echo esc_attr($atts['view']); ?>">
            <div class="document-library-header">
                <h2>
                    <?php 
                    switch($atts['role']) {
                        case 'dasher_mentor':
                            esc_html_e('Mentor Resource Library', 'dasher');
                            break;
                        case 'dasher_bigbird':
                            esc_html_e('BigBird Resource Library', 'dasher');
                            break;
                        case 'dasher_pc':
                            esc_html_e('Program Candidate Resources', 'dasher');
                            break;
                        default:
                            esc_html_e('Document Library', 'dasher');
                    }
                    ?>
                </h2>
                
                <div class="library-controls">
                    <div class="search-controls">
                        <input type="text" id="document-search" placeholder="<?php esc_attr_e('Search documents...', 'dasher'); ?>" class="dasher-search-input">
                        <select id="category-filter" class="dasher-filter-select">
                            <option value=""><?php esc_html_e('All Categories', 'dasher'); ?></option>
                            <option value="guidelines"><?php esc_html_e('Guidelines', 'dasher'); ?></option>
                            <option value="evaluations"><?php esc_html_e('Evaluations', 'dasher'); ?></option>
                            <option value="outlines"><?php esc_html_e('Group Outlines', 'dasher'); ?></option>
                            <option value="exams"><?php esc_html_e('Exams', 'dasher'); ?></option>
                            <option value="templates"><?php esc_html_e('Templates', 'dasher'); ?></option>
                            <option value="training"><?php esc_html_e('Training Materials', 'dasher'); ?></option>
                            <option value="reference"><?php esc_html_e('Reference', 'dasher'); ?></option>
                        </select>
                    </div>
                    
                    <div class="view-controls">
                        <button class="view-toggle <?php echo $atts['view'] === 'grid' ? 'active' : ''; ?>" data-view="grid">
                            <i class="fas fa-th"></i>
                        </button>
                        <button class="view-toggle <?php echo $atts['view'] === 'list' ? 'active' : ''; ?>" data-view="list">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <?php if (empty($documents)) : ?>
                <div class="no-documents">
                    <i class="fas fa-folder-open"></i>
                    <p><?php esc_html_e('No documents available yet.', 'dasher'); ?></p>
                </div>
            <?php else : ?>
                <div class="document-grid" id="document-grid">
                    <?php foreach ($documents as $doc) : 
                        // Handle both regular posts and LearnDash materials
                        $is_learndash = isset($doc->post_type) && $doc->post_type === 'learndash_material';
                        
                        if ($is_learndash) {
                            $category = $doc->_dasher_document_category;
                            $is_printable = $doc->_dasher_is_printable;
                            $source_file = $doc->_dasher_source_file;
                            $download_url = $doc->guid;
                        } else {
                            $category = get_post_meta($doc->ID, '_dasher_document_category', true);
                            $is_printable = get_post_meta($doc->ID, '_dasher_is_printable', true);
                            $source_file = get_post_meta($doc->ID, '_dasher_source_file', true);
                            $download_url = get_permalink($doc->ID);
                        }
                        
                        $file_ext = $source_file ? strtolower(pathinfo($source_file, PATHINFO_EXTENSION)) : 'page';
                        $icon_class = $this->get_document_icon($file_ext);
                        ?>
                        <div class="document-item" data-category="<?php echo esc_attr($category); ?>" data-title="<?php echo esc_attr(strtolower($doc->post_title)); ?>">
                            <div class="document-card <?php echo $is_learndash ? 'learndash-material' : 'uploaded-document'; ?>">
                                <div class="document-icon">
                                    <i class="<?php echo esc_attr($icon_class); ?>"></i>
                                    <?php if ($is_printable) : ?>
                                        <span class="printable-badge" title="<?php esc_attr_e('Printable', 'dasher'); ?>">
                                            <i class="fas fa-print"></i>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($is_learndash) : ?>
                                        <span class="learndash-badge" title="<?php esc_attr_e('Course Material', 'dasher'); ?>">
                                            <i class="fas fa-graduation-cap"></i>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="document-content">
                                    <h3 class="document-title"><?php echo esc_html($doc->post_title); ?></h3>
                                    
                                    <div class="document-meta">
                                        <?php if ($category) : ?>
                                            <span class="document-category category-<?php echo esc_attr($category); ?>">
                                                <?php echo esc_html(ucfirst($category)); ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <span class="document-date">
                                            <?php echo human_time_diff(strtotime($doc->post_modified), current_time('timestamp')) . ' ago'; ?>
                                        </span>
                                        
                                        <?php if ($is_learndash) : ?>
                                            <span class="document-source">
                                                <i class="fas fa-book"></i> Course Material
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($doc->post_excerpt) : ?>
                                        <p class="document-excerpt"><?php echo esc_html($doc->post_excerpt); ?></p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="document-actions">
                                    <?php if ($is_learndash) : ?>
                                        <a href="<?php echo esc_url($download_url); ?>" target="_blank" class="btn-primary" download>
                                            <i class="fas fa-download"></i>
                                            <?php esc_html_e('Download', 'dasher'); ?>
                                        </a>
                                        <a href="<?php echo esc_url($download_url); ?>" target="_blank" class="btn-secondary">
                                            <i class="fas fa-external-link-alt"></i>
                                            <?php esc_html_e('View', 'dasher'); ?>
                                        </a>
                                    <?php else : ?>
                                        <a href="<?php echo get_permalink($doc->ID); ?>" class="btn-primary">
                                            <i class="fas fa-eye"></i>
                                            <?php esc_html_e('View', 'dasher'); ?>
                                        </a>
                                        
                                        <?php if ($is_printable) : ?>
                                            <button onclick="printDocument(<?php echo $doc->ID; ?>)" class="btn-secondary">
                                                <i class="fas fa-print"></i>
                                                <?php esc_html_e('Print', 'dasher'); ?>
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <style>
        .dasher-document-library {
            padding: 20px 0;
        }
        
        .document-library-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            gap: 20px;
        }
        
        .document-library-header h2 {
            margin: 0;
            color: var(--bb-heading-text-color);
        }
        
        .library-controls {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .search-controls {
            display: flex;
            gap: 10px;
        }
        
        .dasher-search-input, .dasher-filter-select {
            padding: 8px 12px;
            border: 1px solid var(--bb-body-blocks-border);
            border-radius: var(--bb-block-radius, 6px);
            font-size: 14px;
        }
        
        .dasher-search-input {
            min-width: 200px;
        }
        
        .view-controls {
            display: flex;
            gap: 5px;
        }
        
        .view-toggle {
            padding: 8px 12px;
            border: 1px solid var(--bb-body-blocks-border);
            background: var(--bb-body-blocks);
            color: var(--bb-body-text-color);
            cursor: pointer;
            border-radius: var(--bb-block-radius, 6px);
        }
        
        .view-toggle.active {
            background: var(--bb-primary-button-background-regular);
            color: var(--bb-primary-button-text-regular);
            border-color: var(--bb-primary-button-border-regular);
        }
        
        .document-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .dasher-document-library[data-view="list"] .document-grid {
            grid-template-columns: 1fr;
        }
        
        .dasher-document-library[data-view="list"] .document-card {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .dasher-document-library[data-view="list"] .document-icon {
            margin-bottom: 0;
        }
        
        .dasher-document-library[data-view="list"] .document-content {
            flex: 1;
        }
        
        .document-card {
            background: var(--bb-body-blocks);
            border: 1px solid var(--bb-body-blocks-border);
            border-radius: var(--bb-block-radius, 12px);
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .document-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .document-icon {
            position: relative;
            text-align: center;
            margin-bottom: 15px;
        }
        
        .document-icon i {
            font-size: 48px;
            color: var(--bb-primary-button-background-regular);
        }
        
        .printable-badge {
            position: absolute;
            top: -5px;
            right: 50%;
            transform: translateX(50%);
            background: var(--bb-success-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            z-index: 2;
        }
        
        .learndash-badge {
            position: absolute;
            top: -5px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--bb-primary-button-background-regular);
            color: var(--bb-primary-button-text-regular);
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            z-index: 2;
        }
        
        .document-card.learndash-material {
            border-left: 4px solid var(--bb-primary-button-background-regular);
        }
        
        .document-card.uploaded-document {
            border-left: 4px solid var(--bb-secondary-button-background-regular);
        }
        
        .document-source {
            color: var(--bb-primary-button-background-regular);
            font-size: 11px;
            font-weight: 500;
        }
        
        .document-title {
            margin: 0 0 10px 0;
            font-size: 18px;
            color: var(--bb-heading-text-color);
            line-height: 1.4;
        }
        
        .document-meta {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 10px;
            font-size: 12px;
        }
        
        .document-category {
            padding: 2px 8px;
            border-radius: 12px;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 11px;
        }
        
        .category-guidelines { background: #e3f2fd; color: #1565c0; }
        .category-evaluations { background: #fff3e0; color: #ef6c00; }
        .category-outlines { background: #f3e5f5; color: #7b1fa2; }
        .category-exams { background: #ffebee; color: #c62828; }
        .category-templates { background: #e8f5e8; color: #388e3c; }
        .category-training { background: #fff8e1; color: #f57c00; }
        .category-reference { background: #fafafa; color: #616161; }
        
        .document-date {
            color: var(--bb-alternate-text-color);
        }
        
        .document-excerpt {
            font-size: 14px;
            color: var(--bb-body-text-color);
            line-height: 1.5;
            margin: 10px 0;
        }
        
        .document-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-primary, .btn-secondary {
            padding: 8px 16px;
            border-radius: var(--bb-block-radius, 6px);
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            border: 1px solid transparent;
        }
        
        .btn-primary {
            background: var(--bb-primary-button-background-regular);
            color: var(--bb-primary-button-text-regular);
            border-color: var(--bb-primary-button-border-regular);
        }
        
        .btn-secondary {
            background: var(--bb-secondary-button-background-regular);
            color: var(--bb-secondary-button-text-regular);
            border-color: var(--bb-secondary-button-border-regular);
        }
        
        .no-documents {
            text-align: center;
            padding: 60px 20px;
            color: var(--bb-alternate-text-color);
        }
        
        .no-documents i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .document-library-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .library-controls {
                flex-direction: column;
                gap: 15px;
            }
            
            .search-controls {
                flex-direction: column;
            }
            
            .dasher-search-input {
                min-width: auto;
                width: 100%;
            }
            
            .document-grid {
                grid-template-columns: 1fr;
            }
        }
        </style>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('document-search');
            const categoryFilter = document.getElementById('category-filter');
            const viewToggles = document.querySelectorAll('.view-toggle');
            const library = document.querySelector('.dasher-document-library');
            
            // Search functionality
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    filterDocuments();
                });
            }
            
            // Category filter
            if (categoryFilter) {
                categoryFilter.addEventListener('change', function() {
                    filterDocuments();
                });
            }
            
            // View toggle
            viewToggles.forEach(toggle => {
                toggle.addEventListener('click', function() {
                    const view = this.dataset.view;
                    viewToggles.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    library.dataset.view = view;
                });
            });
            
            function filterDocuments() {
                const searchTerm = searchInput.value.toLowerCase();
                const selectedCategory = categoryFilter.value;
                const documents = document.querySelectorAll('.document-item');
                
                documents.forEach(doc => {
                    const title = doc.dataset.title;
                    const category = doc.dataset.category;
                    
                    const matchesSearch = !searchTerm || title.includes(searchTerm);
                    const matchesCategory = !selectedCategory || category === selectedCategory;
                    
                    if (matchesSearch && matchesCategory) {
                        doc.style.display = 'block';
                    } else {
                        doc.style.display = 'none';
                    }
                });
            }
        });
        
        function printDocument(docId) {
            const printWindow = window.open('/wp-admin/admin-ajax.php?action=dasher_print_document&doc_id=' + docId, '_blank');
            printWindow.print();
        }
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get document icon based on file extension
     */
    private function get_document_icon($ext) {
        $icons = array(
            'pdf' => 'fas fa-file-pdf',
            'doc' => 'fas fa-file-word',
            'docx' => 'fas fa-file-word',
            'xls' => 'fas fa-file-excel',
            'xlsx' => 'fas fa-file-excel',
            'txt' => 'fas fa-file-alt',
            'page' => 'fas fa-file-alt'
        );
        
        return isset($icons[$ext]) ? $icons[$ext] : 'fas fa-file';
    }
    
    /**
     * Enhanced get documents for role with filtering
     */
    private function get_documents_for_role($role, $category = '', $limit = -1) {
        $documents = array();
        
        // Get uploaded documents
        $meta_query = array(
            array(
                'key' => '_dasher_access_roles',
                'value' => $role,
                'compare' => 'LIKE'
            )
        );
        
        if (!empty($category)) {
            $meta_query[] = array(
                'key' => '_dasher_document_category',
                'value' => $category,
                'compare' => '='
            );
        }
        
        $args = array(
            'post_type' => 'dasher_document',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'meta_query' => $meta_query,
            'orderby' => 'post_modified',
            'order' => 'DESC'
        );
        
        $uploaded_docs = get_posts($args);
        
        // Get LearnDash lesson materials
        $learndash_materials = $this->get_learndash_materials_for_role($role, $category);
        
        // Combine both types
        $documents = array_merge($uploaded_docs, $learndash_materials);
        
        // Sort by title
        usort($documents, function($a, $b) {
            return strcmp($a->post_title, $b->post_title);
        });
        
        if ($limit > 0) {
            $documents = array_slice($documents, 0, $limit);
        }
        
        return $documents;
    }
    
    /**
     * Get LearnDash lesson materials for a specific role
     */
    private function get_learndash_materials_for_role($role, $category = '') {
        $materials = array();
        
        // Define LCCP course mapping
        $course_mapping = array(
            'dasher_mentor' => array(227700), // Unit 1: Mentor Training Program
            'dasher_bigbird' => array(222793, 222791, 222789, 222787, 222785, 222783, 222781, 222779, 222777), // Units 1-9
            'dasher_pc' => array(222793, 222791, 222789, 222787, 222785, 222783, 222781, 222779, 222777) // Units 1-9
        );
        
        if (!isset($course_mapping[$role])) {
            return $materials;
        }
        
        $course_ids = $course_mapping[$role];
        
        foreach ($course_ids as $course_id) {
            // Get lessons for this course
            $lessons = get_posts(array(
                'post_type' => 'sfwd-lessons',
                'posts_per_page' => -1,
                'meta_query' => array(
                    array(
                        'key' => 'course_id',
                        'value' => $course_id,
                        'compare' => '='
                    )
                )
            ));
            
            foreach ($lessons as $lesson) {
                $lesson_materials = $this->extract_lesson_materials($lesson);
                $materials = array_merge($materials, $lesson_materials);
            }
        }
        
        // Filter by category if specified
        if (!empty($category)) {
            $materials = array_filter($materials, function($material) use ($category) {
                return isset($material->_dasher_document_category) && $material->_dasher_document_category === $category;
            });
        }
        
        return $materials;
    }
    
    /**
     * Extract materials from a LearnDash lesson
     */
    private function extract_lesson_materials($lesson) {
        $materials = array();
        
        $lesson_meta = get_post_meta($lesson->ID, '_sfwd-lessons', true);
        if (!$lesson_meta || !is_array($lesson_meta)) {
            return $materials;
        }
        
        $lesson_materials = isset($lesson_meta['sfwd-lessons_lesson_materials']) ? $lesson_meta['sfwd-lessons_lesson_materials'] : '';
        
        if (empty($lesson_materials)) {
            return $materials;
        }
        
        // Parse HTML to extract links
        $dom = new DOMDocument();
        @$dom->loadHTML($lesson_materials);
        $links = $dom->getElementsByTagName('a');
        
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            $title = trim($link->textContent);
            
            if (!empty($href) && !empty($title)) {
                // Create a virtual post object for the material
                $material = new stdClass();
                $material->ID = 'ld_' . $lesson->ID . '_' . md5($href);
                $material->post_title = $title;
                $material->post_content = sprintf(
                    '<div class="learndash-material">
                        <h3>%s</h3>
                        <p>From lesson: <em>%s</em></p>
                        <div class="material-actions">
                            <a href="%s" target="_blank" class="btn-primary" download>
                                <i class="fas fa-download"></i> Download
                            </a>
                            <a href="%s" target="_blank" class="btn-secondary">
                                <i class="fas fa-external-link-alt"></i> View
                            </a>
                        </div>
                    </div>',
                    esc_html($title),
                    esc_html($lesson->post_title),
                    esc_url($href),
                    esc_url($href)
                );
                $material->post_excerpt = 'From: ' . $lesson->post_title;
                $material->post_modified = $lesson->post_modified;
                $material->post_type = 'learndash_material';
                $material->guid = $href;
                
                // Categorize based on title and lesson
                $category = $this->categorize_learndash_material($title, $lesson->post_title);
                
                // Store category as meta (simulate)
                $material->_dasher_document_category = $category;
                $material->_dasher_source_file = basename(parse_url($href, PHP_URL_PATH));
                $material->_dasher_is_printable = '1';
                $material->_learndash_lesson_id = $lesson->ID;
                $material->_learndash_course_id = get_post_meta($lesson->ID, 'course_id', true);
                
                $materials[] = $material;
            }
        }
        
        return $materials;
    }
    
    /**
     * Categorize LearnDash materials based on title and lesson context
     */
    private function categorize_learndash_material($title, $lesson_title) {
        $title_lower = strtolower($title);
        $lesson_lower = strtolower($lesson_title);
        
        // Category mapping based on content
        if (strpos($title_lower, 'exam') !== false || strpos($lesson_lower, 'exam') !== false) {
            return 'exams';
        } elseif (strpos($title_lower, 'evaluation') !== false || strpos($title_lower, 'self-evaluation') !== false) {
            return 'evaluations';  
        } elseif (strpos($title_lower, 'study guide') !== false || strpos($title_lower, 'key concepts') !== false) {
            return 'guidelines';
        } elseif (strpos($title_lower, 'session summary') !== false || strpos($title_lower, 'assignment') !== false) {
            return 'templates';
        } elseif (strpos($title_lower, 'toolkit') !== false || strpos($lesson_lower, 'toolkit') !== false) {
            return 'training';
        } elseif (strpos($lesson_lower, 'development') !== false) {
            return 'reference';
        } else {
            return 'training'; // Default for LCCP materials
        }
    }
    
    /**
     * Create document library pages if they don't exist
     */
    public function create_document_library_pages() {
        // Only run once
        if (get_option('dasher_document_pages_created')) {
            return;
        }
        
        $pages = array(
            'mentor-library' => array(
                'title' => 'Mentor Resource Library',
                'content' => '[dasher_document_library role="dasher_mentor"]',
                'meta_key' => '_dasher_access_roles',
                'meta_value' => array('dasher_mentor', 'administrator')
            ),
            'bigbird-library' => array(
                'title' => 'BigBird Resource Library', 
                'content' => '[dasher_document_library role="dasher_bigbird"]',
                'meta_key' => '_dasher_access_roles',
                'meta_value' => array('dasher_bigbird', 'administrator')
            ),
            'pc-resources' => array(
                'title' => 'Program Candidate Resources',
                'content' => '[dasher_document_library role="dasher_pc"]', 
                'meta_key' => '_dasher_access_roles',
                'meta_value' => array('dasher_pc', 'administrator')
            )
        );
        
        foreach ($pages as $slug => $page_data) {
            // Check if page already exists
            $existing_page = get_page_by_path($slug);
            
            if (!$existing_page) {
                $page_id = wp_insert_post(array(
                    'post_title' => $page_data['title'],
                    'post_content' => $page_data['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_name' => $slug,
                    'comment_status' => 'closed',
                    'ping_status' => 'closed'
                ));
                
                if ($page_id && !is_wp_error($page_id)) {
                    // Set access control
                    update_post_meta($page_id, $page_data['meta_key'], $page_data['meta_value']);
                }
            }
        }
        
        // Mark as created
        update_option('dasher_document_pages_created', true);
    }
}

// Hook to save document meta
add_action('save_post', array(Dasher_Document_Manager::getInstance(), 'save_document_meta'));

// Initialize the document manager
Dasher_Document_Manager::getInstance();