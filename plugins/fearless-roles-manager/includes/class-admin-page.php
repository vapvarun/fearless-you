<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class FRM_Admin_Page {
    
    /**
     * Render the admin page
     */
    public function render_page() {
        $roles_by_category = FRM_Roles_Manager::get_roles_by_category();
        $wp_fusion_tags = FRM_Roles_Manager::get_wp_fusion_tags();
        $dashboard_pages = FRM_Roles_Manager::get_dashboard_pages();
        $category_stats = FRM_Roles_Manager::get_category_statistics();
        ?>
        <div class="wrap frm-admin-wrap">
            <h1><span class="dashicons dashicons-groups"></span> Roles Manager</h1>
            
            <div class="frm-notice notice notice-info">
                <p>Manage WordPress roles with WP Fusion tag synchronization, permissions overview, and custom dashboard landing pages.</p>
            </div>
            
            <!-- Category Overview -->
            <div class="frm-category-overview">
                <h2>Role Categories</h2>
                <div class="frm-category-stats">
                    <?php foreach ($category_stats as $category_key => $stats): ?>
                        <div class="frm-category-stat-card" style="border-left-color: <?php echo FRM_Roles_Manager::get_role_categories()[$category_key]['color']; ?>">
                            <div class="frm-category-icon">
                                <span class="dashicons <?php echo FRM_Roles_Manager::get_role_categories()[$category_key]['icon']; ?>"></span>
                            </div>
                            <div class="frm-category-info">
                                <h3><?php echo esc_html($stats['name']); ?></h3>
                                <p><?php echo $stats['role_count']; ?> roles • <?php echo $stats['user_count']; ?> users</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Roles by Category -->
            <div class="frm-roles-by-category">
                <?php foreach ($roles_by_category as $category_key => $category_data): ?>
                    <div class="frm-category-section" data-category="<?php echo esc_attr($category_key); ?>">
                        <div class="frm-category-header" style="border-left-color: <?php echo $category_data['category']['color']; ?>">
                            <div class="frm-category-title">
                                <span class="dashicons <?php echo $category_data['category']['icon']; ?>"></span>
                                <h2><?php echo esc_html($category_data['category']['name']); ?></h2>
                                <span class="frm-category-count"><?php echo count($category_data['roles']); ?> roles</span>
                            </div>
                            <p class="frm-category-description"><?php echo esc_html($category_data['category']['description']); ?></p>
                        </div>
                        
                        <div class="frm-roles-container">
                            <?php foreach ($category_data['roles'] as $role_key => $role_data): ?>
                                <div class="frm-role-card" data-role="<?php echo esc_attr($role_key); ?>">
                                    <div class="frm-role-header">
                                        <h3><?php echo esc_html($role_data['name']); ?></h3>
                                        <span class="frm-role-key"><?php echo esc_html($role_key); ?></span>
                                    </div>
                        
                                    <div class="frm-role-body">
                                        <!-- WP Fusion Tags Section -->
                                        <div class="frm-section">
                                            <h3><span class="dashicons dashicons-tag"></span> WP Fusion Tags</h3>
                                            <?php if (function_exists('wp_fusion')): ?>
                                                <div class="frm-tags-container">
                                                    <div class="frm-tags-input-wrapper">
                                                        <input type="text" 
                                                               class="frm-tags-input" 
                                                               placeholder="Type to search WP Fusion tags..." 
                                                               data-role="<?php echo esc_attr($role_key); ?>"
                                                               autocomplete="off">
                                                        <div class="frm-tags-dropdown" style="display: none;"></div>
                                                    </div>
                                                    <div class="frm-selected-tags" data-role="<?php echo esc_attr($role_key); ?>">
                                                        <?php if (!empty($role_data['wp_fusion_tags'])): ?>
                                                            <?php foreach ($role_data['wp_fusion_tags'] as $tag_id): ?>
                                                                <?php if (isset($wp_fusion_tags[$tag_id])): ?>
                                                                    <span class="frm-tag-item" data-tag-id="<?php echo esc_attr($tag_id); ?>">
                                                                        <?php echo esc_html($wp_fusion_tags[$tag_id]); ?>
                                                                        <button type="button" class="frm-remove-tag" data-tag-id="<?php echo esc_attr($tag_id); ?>">×</button>
                                                                    </span>
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <p class="description">Type to search and select tags that should automatically assign this role when synced from your CRM.</p>
                                                </div>
                                            <?php else: ?>
                                                <p class="frm-warning">WP Fusion plugin is not active. Install and activate WP Fusion to enable tag synchronization.</p>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Permissions Section -->
                                        <div class="frm-section">
                                            <h3><span class="dashicons dashicons-lock"></span> Permissions</h3>
                                            <div class="frm-capabilities-container">
                                                <?php 
                                                $grouped_caps = FRM_Roles_Manager::get_formatted_capabilities($role_data['capabilities']);
                                                ?>
                                                
                                                <?php if (empty($grouped_caps)): ?>
                                                    <p class="frm-no-caps">No capabilities assigned to this role.</p>
                                                <?php else: ?>
                                                    <div class="frm-caps-grid">
                                                        <?php foreach ($grouped_caps as $group => $caps): ?>
                                                            <div class="frm-cap-group">
                                                                <h4><?php echo esc_html($group); ?></h4>
                                                                <ul class="frm-cap-list">
                                                                    <?php foreach ($caps as $cap): ?>
                                                                        <li>
                                                                            <span class="dashicons dashicons-yes-alt"></span>
                                                                            <?php echo esc_html($cap); ?>
                                                                        </li>
                                                                    <?php endforeach; ?>
                                                                </ul>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <button class="button frm-toggle-caps" data-role="<?php echo esc_attr($role_key); ?>">
                                                    <span class="dashicons dashicons-visibility"></span> View All Capabilities
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <!-- Dashboard Landing Page Section -->
                                        <div class="frm-section">
                                            <h3><span class="dashicons dashicons-admin-home"></span> Dashboard Landing Page</h3>
                                            <div class="frm-dashboard-container">
                                                <select class="frm-dashboard-select" data-role="<?php echo esc_attr($role_key); ?>">
                                                    <?php foreach ($dashboard_pages as $page_url => $page_name): ?>
                                                        <?php
                                                        // Check if this is a separator
                                                        $is_separator = (strpos($page_url, '--separator') === 0);
                                                        $selected = ($role_data['dashboard_page'] == $page_url) ? 'selected' : '';
                                                        ?>
                                                        <?php if ($is_separator): ?>
                                                            <option disabled><?php echo esc_html($page_name); ?></option>
                                                        <?php else: ?>
                                                            <option value="<?php echo esc_attr($page_url); ?>" <?php echo $selected; ?>>
                                                                <?php echo esc_html($page_name); ?>
                                                            </option>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </select>
                                                <p class="description">Select the default landing page for users with this role after login. The default uses the homepage set in Reading Settings.</p>
                                            </div>
                                        </div>
                                        
                                        <!-- Role Description -->
                                        <div class="frm-section">
                                            <h3><span class="dashicons dashicons-edit"></span> Role Description</h3>
                                            <textarea class="frm-role-description" data-role="<?php echo esc_attr($role_key); ?>" 
                                                      placeholder="Add a description for this role..."><?php echo esc_textarea($role_data['description']); ?></textarea>
                                        </div>
                                        
                                        <!-- Save Button -->
                                        <div class="frm-actions">
                                            <button class="button button-primary frm-save-role" data-role="<?php echo esc_attr($role_key); ?>">
                                                <span class="dashicons dashicons-saved"></span> Save Role Settings
                                            </button>
                                            <span class="frm-save-status"></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- All Capabilities Modal -->
            <div id="frm-caps-modal" class="frm-modal">
                <div class="frm-modal-content">
                    <span class="frm-modal-close">&times;</span>
                    <h2>All Capabilities</h2>
                    <div id="frm-modal-caps-list"></div>
                </div>
            </div>
        </div>
        <?php
    }
}