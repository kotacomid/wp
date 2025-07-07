console.log('template-editor.js loaded');
/**
 * Advanced Template Editor with Visual Builder
 */

;(($) => {
  const kotacomAI = window.kotacomAI // Declare kotacomAI variable

  class TemplateEditor {
    constructor() {
      this.currentTemplate = null
      this.previewMode = false
      this.dragDropEnabled = false
      this.activeComponentSettings = null // To store the settings element of the currently edited component

      this.init()
    }

    init() {
      this.initDragDrop()
      this.initPreview()
      this.initShortcodeBuilder()
      this.initVariableManager()
      this.bindEvents()
      this.loadInitialTemplate() // Load template if ID is in URL
    }

    /**
     * Initialize drag and drop interface
     */
    initDragDrop() {
      // Make template sections sortable
      $("#template-builder").sortable({
        handle: ".section-handle",
        placeholder: "section-placeholder",
        forcePlaceholderSize: true,
        opacity: 0.7,
        tolerance: "pointer",
        update: (event, ui) => {
          this.updateTemplateStructure()
        },
      })

      // Draggable components
      $(".template-component").draggable({
        helper: "clone",
        connectToSortable: "#template-builder",
        revert: "invalid",
        cursor: "move",
        start: (event, ui) => {
          $(ui.helper).addClass("dragging")
        },
        stop: (event, ui) => {
          $(ui.helper).removeClass("dragging")
        },
      })

      // Droppable template builder
      $("#template-builder").droppable({
        accept: ".template-component",
        drop: (event, ui) => {
          this.addComponent(ui.draggable.data("component-type"))
        },
      })
    }

    /**
     * Initialize live preview
     */
    initPreview() {
      $("#preview-template").on("click", () => {
        this.togglePreview()
      })

      $("#refresh-preview").on("click", () => {
        this.updatePreview()
      })

      // Auto-preview on content change (for shortcode editor)
      let previewTimeout
      $("#template-content").on("input", () => {
        clearTimeout(previewTimeout)
        previewTimeout = setTimeout(() => {
          this.updatePreview()
        }, 1000)
      })

      // Auto-preview on visual builder content change
      $(document).on(
        "input change",
        "#template-builder .component-settings input, #template-builder .component-settings select, #template-builder .component-settings textarea",
        () => {
          clearTimeout(previewTimeout)
          previewTimeout = setTimeout(() => {
            this.updateTemplateStructure() // Update hidden content first
            this.updatePreview()
          }, 1000)
        },
      )

      $("#preview-keyword").on("input", () => {
        clearTimeout(previewTimeout)
        previewTimeout = setTimeout(() => {
          this.updatePreview()
        }, 1000)
      })
    }

    /**
     * Initialize shortcode builder
     */
    initShortcodeBuilder() {
      $("#add-ai-content").on("click", () => {
        this.openShortcodeBuilder("ai_content")
      })

      $("#add-ai-section").on("click", () => {
        this.openShortcodeBuilder("ai_section")
      })

      $("#add-ai-list").on("click", () => {
        this.openShortcodeBuilder("ai_list")
      })

      $("#add-conditional").on("click", () => {
        this.openShortcodeBuilder("ai_conditional")
      })

      $("#insert-shortcode").on("click", () => {
        this.insertShortcode()
      })

      $("#cancel-shortcode, .kotacom-modal-close").on("click", () => {
        $("#shortcode-builder-modal").hide()
      })
    }

    /**
     * Initialize variable manager
     */
    initVariableManager() {
      const self = this

      $("#add-variable").on("click", () => {
        self.addVariable()
      })

      $(document).on("click", ".remove-variable", function () {
        $(this).closest(".variable-row").remove()
        self.updateTemplateStructure() // Variables are part of template settings
      })
    }

    /**
     * Bind events
     */
    bindEvents() {
      const self = this

      $("#save-template").on("click", () => {
        self.saveTemplate()
      })

      $("#load-template").on("change", function () {
        const templateId = $(this).val()
        if (templateId) {
          self.loadTemplate(templateId)
        }
      })

      $("#duplicate-template").on("click", () => {
        self.duplicateTemplate()
      })

      // Template type change
      $("#template-type").on("change", function () {
        self.switchTemplateType($(this).val())
      })

      // Clear template builder
      $("#clear-template").on("click", () => {
        if (confirm("Are you sure you want to clear the entire template?")) {
          $("#template-builder").empty().append(this.getBuilderPlaceholder())
          $("#template-content").val("") // Clear shortcode editor too
          this.updateTemplateStructure()
        }
      })

      // Edit/Remove component actions
      $(document).on("click", ".edit-component", function () {
        const targetId = $(this).data("target")
        const $section = $("#" + targetId)
        const $settings = $section.find(".component-settings")

        // Toggle visibility
        $settings.slideToggle(200)

        // Update button text
        if ($(this).text() === "Edit") {
          $(this).text("Close")
        } else {
          $(this).text("Edit")
        }
      })

      $(document).on("click", ".remove-component", function () {
        if (confirm("Are you sure you want to remove this component?")) {
          $(this).closest(".template-section").remove()
          self.updateTemplateStructure()
        }
      })

      // Generate Post button handler with log and kotacomAI check
      $("#generate-post").on("click", function() {
        console.log("Generate Post button clicked");
        if (typeof kotacomAI === 'undefined' || !kotacomAI.ajaxurl || !kotacomAI.nonce) {
          alert("kotacomAI config not found. Please reload the page or contact admin.");
          console.error("kotacomAI config missing:", window.kotacomAI);
          return;
        }
        const keyword = $("#generate-keyword").val();
        const templateContent = $("#template-content").val();
        if (!keyword) {
          alert("Please enter a keyword.");
          return;
        }
        console.log("Sending AJAX to:", kotacomAI.ajaxurl, { keyword, templateContent });
        $.post(kotacomAI.ajaxurl, {
          action: "kotacom_generate_post",
          nonce: kotacomAI.nonce,
          template_content: templateContent,
          keyword: keyword
        }, function(response) {
          console.log("AJAX response:", response);
          if (response.success) {
            alert("Post generated successfully!");
            // Optionally redirect to the new post
            // window.location.href = response.data.edit_link;
          } else {
            alert("Failed to generate post: " + (response.data && response.data.message ? response.data.message : 'Unknown error'));
          }
        }).fail(function(xhr, status, error) {
          alert("AJAX request failed: " + error);
          console.error("AJAX error:", status, error, xhr);
        });
      });
    }

    /**
     * Load template if ID is in URL
     */
    loadInitialTemplate() {
      const urlParams = new URLSearchParams(window.location.search)
      const templateId = urlParams.get("template_id")
      if (templateId) {
        $("#load-template").val(templateId) // Select in dropdown
        this.loadTemplate(templateId)
      }
    }

    /**
     * Add component to template
     */
    addComponent(componentType) {
      let componentHtml = ""

      switch (componentType) {
        case "ai-content":
          componentHtml = this.createAIContentComponent()
          break
        case "ai-section":
          componentHtml = this.createAISectionComponent()
          break
        case "ai-list":
          componentHtml = this.createAIListComponent()
          break
        case "static-content":
          componentHtml = this.createStaticContentComponent()
          break
        case "conditional":
          componentHtml = this.createConditionalComponent()
          break
        case "separator":
          componentHtml = this.createSeparatorComponent()
          break
      }

      // Remove placeholder if present
      $("#template-builder .builder-placeholder").remove()
      $("#template-builder").append(componentHtml)
      this.updateTemplateStructure()
    }

    /**
     * Create AI content component
     */
    createAIContentComponent(settings = {}) {
      const id = "component-" + Date.now()
      const defaultSettings = {
        type: "paragraph",
        prompt: "Write about {keyword}",
        length: "200",
        tone: "informative",
        fallback: "",
        cache: "true",
      }
      const mergedSettings = { ...defaultSettings, ...settings }

      return `
                <div class="template-section" data-component="ai-content" id="${id}">
                    <div class="section-header">
                        <span class="section-handle">⋮⋮</span>
                        <span class="section-title">AI Content Block</span>
                        <div class="section-actions">
                            <button class="edit-component button button-small" data-target="${id}">Edit</button>
                            <button class="remove-component button button-small button-danger" data-target="${id}">Remove</button>
                        </div>
                    </div>
                    <div class="section-content">
                        <div class="component-preview">
                            [ai_content type="${mergedSettings.type}" prompt="${mergedSettings.prompt}" length="${mergedSettings.length}" tone="${mergedSettings.tone}"]
                        </div>
                        <div class="component-settings" style="display: none;">
                            <label>Content Type:</label>
                            <select name="type">
                                <option value="paragraph" ${mergedSettings.type === "paragraph" ? "selected" : ""}>Paragraph</option>
                                <option value="heading" ${mergedSettings.type === "heading" ? "selected" : ""}>Heading</option>
                                <option value="list" ${mergedSettings.type === "list" ? "selected" : ""}>List</option>
                            </select>
                            
                            <label>Prompt:</label>
                            <textarea name="prompt" placeholder="Write about {keyword}">${mergedSettings.prompt}</textarea>
                            
                            <label>Length (words):</label>
                            <input type="number" name="length" value="${mergedSettings.length}" min="50" max="2000">
                            
                            <label>Tone:</label>
                            <select name="tone">
                                <option value="informative" ${mergedSettings.tone === "informative" ? "selected" : ""}>Informative</option>
                                <option value="casual" ${mergedSettings.tone === "casual" ? "selected" : ""}>Casual</option>
                                <option value="formal" ${mergedSettings.tone === "formal" ? "selected" : ""}>Formal</option>
                                <option value="persuasive" ${mergedSettings.tone === "persuasive" ? "selected" : ""}>Persuasive</option>
                                <option value="creative" ${mergedSettings.tone === "creative" ? "selected" : ""}>Creative</option>
                            </select>

                            <label>Fallback Text:</label>
                            <input type="text" name="fallback" value="${mergedSettings.fallback}" placeholder="Default text if AI fails">
                            
                            <label>
                                <input type="checkbox" name="cache" ${mergedSettings.cache === "true" ? "checked" : ""}> Cache generated content
                            </label>
                        </div>
                    </div>
                </div>
            `
    }

    /**
     * Create AI section component
     */
    createAISectionComponent(settings = {}) {
      const id = "component-" + Date.now()
      const defaultSettings = {
        title: "About {keyword}",
        css_class: "ai-section",
        wrapper: "div",
        content: '[ai_content prompt="Explain {keyword} in detail"]', // Default inner content
      }
      const mergedSettings = { ...defaultSettings, ...settings }

      return `
                <div class="template-section" data-component="ai-section" id="${id}">
                    <div class="section-header">
                        <span class="section-handle">⋮⋮</span>
                        <span class="section-title">AI Section</span>
                        <div class="section-actions">
                            <button class="edit-component button button-small" data-target="${id}">Edit</button>
                            <button class="remove-component button button-small button-danger" data-target="${id}">Remove</button>
                        </div>
                    </div>
                    <div class="section-content">
                        <div class="component-preview">
                            [ai_section title="${mergedSettings.title}" class="${mergedSettings.css_class}" wrapper="${mergedSettings.wrapper}"]
                                ${mergedSettings.content}
                            [/ai_section]
                        </div>
                        <div class="component-settings" style="display: none;">
                            <label>Section Title:</label>
                            <input type="text" name="section_title" value="${mergedSettings.title}">
                            
                            <label>CSS Class:</label>
                            <input type="text" name="css_class" value="${mergedSettings.css_class}">
                            
                            <label>Wrapper Element:</label>
                            <select name="wrapper">
                                <option value="div" ${mergedSettings.wrapper === "div" ? "selected" : ""}>Div</option>
                                <option value="section" ${mergedSettings.wrapper === "section" ? "selected" : ""}>Section</option>
                                <option value="article" ${mergedSettings.wrapper === "article" ? "selected" : ""}>Article</option>
                            </select>

                            <label>Inner Content (Shortcodes):</label>
                            <textarea name="content" rows="5" placeholder="Enter shortcodes for this section">${mergedSettings.content}</textarea>
                        </div>
                    </div>
                </div>
            `
    }

    /**
     * Create AI List component
     */
    createAIListComponent(settings = {}) {
      const id = "component-" + Date.now()
      const defaultSettings = {
        prompt: "Generate a list of 5 benefits of {keyword}",
        type: "ul",
        length: "5",
        item_prefix: "",
        item_suffix: "",
      }
      const mergedSettings = { ...defaultSettings, ...settings }

      return `
                <div class="template-section" data-component="ai-list" id="${id}">
                    <div class="section-header">
                        <span class="section-handle">⋮⋮</span>
                        <span class="section-title">AI List Block</span>
                        <div class="section-actions">
                            <button class="edit-component button button-small" data-target="${id}">Edit</button>
                            <button class="remove-component button button-small button-danger" data-target="${id}">Remove</button>
                        </div>
                    </div>
                    <div class="section-content">
                        <div class="component-preview">
                            [ai_list prompt="${mergedSettings.prompt}" type="${mergedSettings.type}" length="${mergedSettings.length}"]
                        </div>
                        <div class="component-settings" style="display: none;">
                            <label>Prompt:</label>
                            <textarea name="prompt" placeholder="Generate a list of 5 benefits of {keyword}">${mergedSettings.prompt}</textarea>
                            
                            <label>List Type:</label>
                            <select name="type">
                                <option value="ul" ${mergedSettings.type === "ul" ? "selected" : ""}>Unordered List (ul)</option>
                                <option value="ol" ${mergedSettings.type === "ol" ? "selected" : ""}>Ordered List (ol)</option>
                            </select>

                            <label>Number of Items:</label>
                            <input type="number" name="length" value="${mergedSettings.length}" min="1" max="50">

                            <label>Item Prefix (optional):</label>
                            <input type="text" name="item_prefix" value="${mergedSettings.item_prefix}" placeholder="e.g., ✅ ">

                            <label>Item Suffix (optional):</label>
                            <input type="text" name="item_suffix" value="${mergedSettings.item_suffix}" placeholder="e.g., .">
                        </div>
                    </div>
                </div>
            `
    }

    /**
     * Create Static Content component
     */
    createStaticContentComponent(settings = {}) {
      const id = "component-" + Date.now()
      const defaultSettings = {
        content: "Your static content here.",
      }
      const mergedSettings = { ...defaultSettings, ...settings }

      return `
                <div class="template-section" data-component="static-content" id="${id}">
                    <div class="section-header">
                        <span class="section-handle">⋮⋮</span>
                        <span class="section-title">Static Content Block</span>
                        <div class="section-actions">
                            <button class="edit-component button button-small" data-target="${id}">Edit</button>
                            <button class="remove-component button button-small button-danger" data-target="${id}">Remove</button>
                        </div>
                    </div>
                    <div class="section-content">
                        <div class="component-preview static-content-preview">
                            ${mergedSettings.content}
                        </div>
                        <div class="component-settings" style="display: none;">
                            <label>Content (HTML allowed):</label>
                            <textarea name="content" rows="5" class="static-content">${mergedSettings.content}</textarea>
                        </div>
                    </div>
                </div>
            `
    }

    /**
     * Create Conditional component
     */
    createConditionalComponent(settings = {}) {
      const id = "component-" + Date.now()
      const defaultSettings = {
        if: "post_type",
        equals: "post",
        content: "<!-- Content shown if condition is met -->",
      }
      const mergedSettings = { ...defaultSettings, ...settings }

      return `
                <div class="template-section" data-component="conditional" id="${id}">
                    <div class="section-header">
                        <span class="section-handle">⋮⋮</span>
                        <span class="section-title">Conditional Block</span>
                        <div class="section-actions">
                            <button class="edit-component button button-small" data-target="${id}">Edit</button>
                            <button class="remove-component button button-small button-danger" data-target="${id}">Remove</button>
                        </div>
                    </div>
                    <div class="section-content">
                        <div class="component-preview">
                            [ai_conditional if="${mergedSettings.if}" equals="${mergedSettings.equals}"]
                                ${mergedSettings.content}
                            [/ai_conditional]
                        </div>
                        <div class="component-settings" style="display: none;">
                            <label>Condition Variable (e.g., post_type, user_role):</label>
                            <input type="text" name="if" value="${mergedSettings.if}">
                            
                            <label>Equals Value:</label>
                            <input type="text" name="equals" value="${mergedSettings.equals}">

                            <label>Inner Content (Shortcodes/HTML):</label>
                            <textarea name="content" rows="5">${mergedSettings.content}</textarea>
                        </div>
                    </div>
                </div>
            `
    }

    /**
     * Create Separator component
     */
    createSeparatorComponent() {
      const id = "component-" + Date.now()
      return `
                <div class="template-section" data-component="separator" id="${id}">
                    <div class="section-header">
                        <span class="section-handle">⋮⋮</span>
                        <span class="section-title">Separator</span>
                        <div class="section-actions">
                            <button class="remove-component button button-small button-danger" data-target="${id}">Remove</button>
                        </div>
                    </div>
                    <div class="section-content">
                        <hr class="ai-separator" />
                    </div>
                </div>
            `
    }

    /**
     * Toggle preview mode
     */
    togglePreview() {
      this.previewMode = !this.previewMode

      if (this.previewMode) {
        this.showPreview()
        $("#preview-template").text("Edit Mode")
        $("#template-editor-modes").hide()
      } else {
        this.showEditor()
        $("#preview-template").text("Preview")
        $("#template-editor-modes").show()
      }
    }

    /**
     * Show preview
     */
    showPreview() {
      const content = this.generateTemplateContent()
      const keyword = $("#preview-keyword").val() || "example keyword"
      const variables = this.getVariables()

      $.ajax({
        url: kotacomAI.ajaxurl,
        type: "POST",
        data: {
          action: "kotacom_preview_template",
          nonce: kotacomAI.nonce,
          content: content,
          keyword: keyword,
          variables: JSON.stringify(variables), // Send as JSON string
        },
        success: (response) => {
          if (response.success) {
            $("#template-preview .preview-content").html(response.data.preview)
            $("#template-preview").show()
          } else {
            kotacomAI.utils.showNotice("Failed to load preview: " + response.data.message, "error")
          }
        },
        error: () => {
          kotacomAI.utils.showNotice("Error loading preview.", "error")
        },
      })
    }

    /**
     * Show editor
     */
    showEditor() {
      $("#template-preview").hide()
      // Ensure the correct editor mode is shown
      this.switchTemplateType($("#template-type").val())
    }

    /**
     * Update preview
     */
    updatePreview() {
      if (this.previewMode) {
        this.showPreview()
      }
    }

    /**
     * Open shortcode builder modal
     */
    openShortcodeBuilder(shortcodeType) {
      const modal = $("#shortcode-builder-modal")
      const form = $("#shortcode-builder-form")

      // Clear previous form
      form.empty()

      // Build form based on shortcode type
      switch (shortcodeType) {
        case "ai_content":
          this.buildAIContentForm(form)
          break
        case "ai_section":
          this.buildAISectionForm(form)
          break
        case "ai_list":
          this.buildAIListForm(form)
          break
        case "ai_conditional":
          this.buildConditionalForm(form)
          break
      }

      modal.data("shortcode-type", shortcodeType).show()
    }

    /**
     * Build AI content form for modal
     */
    buildAIContentForm(form) {
      form.append(`
                <div class="form-group">
                    <label>Content Type:</label>
                    <select name="type">
                        <option value="paragraph">Paragraph</option>
                        <option value="heading">Heading</option>
                        <option value="list">List</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Prompt:</label>
                    <textarea name="prompt" placeholder="Write about {keyword}" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Length (words):</label>
                    <input type="number" name="length" value="200" min="50" max="2000">
                </div>
                
                <div class="form-group">
                    <label>Tone:</label>
                    <select name="tone">
                        <option value="informative">Informative</option>
                        <option value="casual">Casual</option>
                        <option value="formal">Formal</option>
                        <option value="persuasive">Persuasive</option>
                        <option value="creative">Creative</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Fallback Text:</label>
                    <input type="text" name="fallback" placeholder="Default text if AI fails">
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="cache" checked> Cache generated content
                    </label>
                </div>
            `)
    }

    /**
     * Build AI section form for modal
     */
    buildAISectionForm(form) {
      form.append(`
                <div class="form-group">
                    <label>Section Title:</label>
                    <input type="text" name="title" value="About {keyword}">
                </div>
                <div class="form-group">
                    <label>CSS Class:</label>
                    <input type="text" name="class" value="ai-section">
                </div>
                <div class="form-group">
                    <label>Wrapper Element:</label>
                    <select name="wrapper">
                        <option value="div">Div</option>
                        <option value="section">Section</option>
                        <option value="article">Article</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Inner Content (Shortcodes):</label>
                    <textarea name="content" rows="5" placeholder="[ai_content prompt='Explain {keyword} in detail']"></textarea>
                </div>
            `)
    }

    /**
     * Build AI List form for modal
     */
    buildAIListForm(form) {
      form.append(`
                <div class="form-group">
                    <label>Prompt:</label>
                    <textarea name="prompt" placeholder="Generate a list of 5 benefits of {keyword}" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>List Type:</label>
                    <select name="type">
                        <option value="ul">Unordered List (ul)</option>
                        <option value="ol">Ordered List (ol)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Number of Items:</label>
                    <input type="number" name="length" value="5" min="1" max="50">
                </div>
                <div class="form-group">
                    <label>Item Prefix (optional):</label>
                    <input type="text" name="item_prefix" placeholder="e.g., ✅ ">
                </div>
                <div class="form-group">
                    <label>Item Suffix (optional):</label>
                    <input type="text" name="item_suffix" placeholder="e.g., .">
                </div>
            `)
    }

    /**
     * Build Conditional form for modal
     */
    buildConditionalForm(form) {
      form.append(`
                <div class="form-group">
                    <label>Condition Variable (e.g., post_type, user_role):</label>
                    <input type="text" name="if" value="post_type">
                </div>
                <div class="form-group">
                    <label>Equals Value:</label>
                    <input type="text" name="equals" value="post">
                </div>
                <div class="form-group">
                    <label>Inner Content (Shortcodes/HTML):</label>
                    <textarea name="content" rows="5" placeholder="<!-- Content shown if condition is met -->"></textarea>
                </div>
            `)
    }

    /**
     * Insert shortcode into the editor
     */
    insertShortcode() {
      const modal = $("#shortcode-builder-modal")
      const shortcodeType = modal.data("shortcode-type")
      const form = $("#shortcode-builder-form")
      const settings = {}

      form.find("input, select, textarea").each(function () {
        const $field = $(this)
        const name = $field.attr("name")
        let value = $field.val()

        if ($field.is(":checkbox")) {
          value = $field.is(":checked") ? "true" : "false"
        }
        settings[name] = value
      })

      let shortcode = ""
      switch (shortcodeType) {
        case "ai_content":
          shortcode = this.generateAIContentShortcode(settings)
          break
        case "ai_section":
          shortcode = this.generateAISectionShortcode(settings)
          break
        case "ai_list":
          shortcode = this.generateAIListShortcode(settings)
          break
        case "ai_conditional":
          shortcode = this.generateConditionalShortcode(settings)
          break
      }

      // Insert into the shortcode editor textarea
      const $editor = $("#template-content")
      const currentContent = $editor.val()
      const cursorPosition = $editor.prop("selectionStart")

      const newContent =
        currentContent.substring(0, cursorPosition) + shortcode + currentContent.substring(cursorPosition)

      $editor.val(newContent)
      modal.hide()
      this.updatePreview() // Update preview after inserting shortcode
    }

    /**
     * Generate template content from visual builder
     */
    generateTemplateContent() {
      let content = ""
      const self = this

      $("#template-builder .template-section").each(function () {
        const $section = $(this)
        const componentType = $section.data("component")
        const settings = self.getComponentSettings($section) // Get current settings from the form fields

        switch (componentType) {
          case "ai-content":
            content += self.generateAIContentShortcode(settings)
            break
          case "ai-section":
            content += self.generateAISectionShortcode(settings)
            break
          case "ai-list":
            content += self.generateAIListShortcode(settings)
            break
          case "static-content":
            content += settings.content // Static content is just its HTML
            break
          case "conditional":
            content += self.generateConditionalShortcode(settings)
            break
          case "separator":
            content += '<hr class="ai-separator" />'
            break
        }

        content += "\n\n" // Add newlines for readability
      })

      return content
    }

    /**
     * Generate AI content shortcode string
     */
    generateAIContentShortcode(settings) {
      let shortcode = "[ai_content"
      for (const key in settings) {
        if (settings[key] !== "" && settings[key] !== undefined) {
          shortcode += ` ${key}="${settings[key]}"`
        }
      }
      shortcode += "]"
      return shortcode
    }

    /**
     * Generate AI section shortcode string
     */
    generateAISectionShortcode(settings) {
      let shortcode = `[ai_section title="${settings.section_title || ""}"`
      if (settings.css_class) {
        shortcode += ` class="${settings.css_class}"`
      }
      if (settings.wrapper) {
        shortcode += ` wrapper="${settings.wrapper}"`
      }
      shortcode += `]\n\t${settings.content || ""}\n[/ai_section]`
      return shortcode
    }

    /**
     * Generate AI List shortcode string
     */
    generateAIListShortcode(settings) {
      let shortcode = "[ai_list"
      for (const key in settings) {
        if (settings[key] !== "" && settings[key] !== undefined) {
          shortcode += ` ${key}="${settings[key]}"`
        }
      }
      shortcode += "]"
      return shortcode
    }

    /**
     * Generate Conditional shortcode string
     */
    generateConditionalShortcode(settings) {
      let shortcode = `[ai_conditional if="${settings.if}"`
      if (settings.equals) {
        shortcode += ` equals="${settings.equals}"`
      }
      if (settings.contains) {
        shortcode += ` contains="${settings.contains}"`
      }
      if (settings.not_empty) {
        shortcode += ` not_empty="${settings.not_empty}"`
      }
      if (settings.user_role) {
        shortcode += ` user_role="${settings.user_role}"`
      }
      if (settings.post_type) {
        shortcode += ` post_type="${settings.post_type}"`
      }
      shortcode += `]\n\t${settings.content || ""}\n[/ai_conditional]`
      return shortcode
    }

    /**
     * Get component settings from its form fields
     */
    getComponentSettings($section) {
      const settings = {}

      $section
        .find(".component-settings input, .component-settings select, .component-settings textarea")
        .each(function () {
          const $field = $(this)
          const name = $field.attr("name")
          let value = $field.val()

          if ($field.is(":checkbox")) {
            value = $field.is(":checked") ? "true" : "false"
          }

          // Special handling for static content, where 'content' is the actual HTML
          if ($field.hasClass("static-content")) {
            settings[name] = $field.val() // Get raw HTML
          } else if (value !== null && value !== undefined) {
            settings[name] = String(value) // Ensure string
          }
        })

      return settings
    }

    /**
     * Update the hidden template content textarea and preview
     */
    updateTemplateStructure() {
      const generatedContent = this.generateTemplateContent()
      $("#template-content").val(generatedContent) // Update the hidden textarea
      this.updatePreview() // Update the preview if in preview mode
    }

    /**
     * Save template
     */
    saveTemplate() {
      const templateData = {
        title: $("#template-title").val(),
        content: this.generateTemplateContent(), // Get content from visual builder
        template_type: $("#template-type").val(),
        settings: this.getTemplateSettings(),
        variables: this.getVariables(),
      }

      // If in shortcode editor mode, use its content directly
      if ($("#template-type").val() === "shortcode") {
        templateData.content = $("#template-content").val()
      }

      // Get template ID if editing existing
      const urlParams = new URLSearchParams(window.location.search)
      const templateId = urlParams.get("template_id")
      if (templateId) {
        templateData.id = templateId
      }

      $.ajax({
        url: kotacomAI.ajaxurl,
        type: "POST",
        data: {
          action: "kotacom_save_template",
          nonce: kotacomAI.nonce,
          ...templateData,
        },
        success: function (response) {
          if (response.success) {
            kotacomAI.utils.showNotice("Template saved successfully!", "success")
            // If new template, update URL to include ID
            if (!templateId && response.data.template_id) {
              const newUrl = new URL(window.location.href)
              newUrl.searchParams.set("template_id", response.data.template_id)
              window.history.pushState({ path: newUrl.href }, "", newUrl.href)
            }
            this.refreshTemplateList()
          } else {
            kotacomAI.utils.showNotice("Failed to save template: " + response.data.message, "error")
          }
        }.bind(this),
        error: () => {
          kotacomAI.utils.showNotice("Error saving template.", "error")
        },
      })
    }

    /**
     * Load template by ID
     */
    loadTemplate(templateId) {
      $.ajax({
        url: kotacomAI.ajaxurl,
        type: "POST",
        data: {
          action: "kotacom_get_template", // Need a new AJAX action for this
          nonce: kotacomAI.nonce,
          template_id: templateId,
        },
        success: (response) => {
          if (response.success && response.data.template) {
            const template = response.data.template
            $("#template-title").val(template.title)
            $("#template-type").val(template.template_type).trigger("change") // Trigger change to switch editor mode

            // Load settings
            const settings = JSON.parse(template.settings || "{}")
            $("#auto-generate").prop("checked", settings.auto_generate === "true")
            $("#cache-duration").val(settings.cache_duration || "3600")

            // Load variables
            $("#variables-list").empty()
            const variables = JSON.parse(template.variables || "{}")
            for (const name in variables) {
              this.addVariable(name, variables[name].default, variables[name].description)
            }

            // Load content based on type
            if (template.template_type === "visual") {
              this.parseContentToVisualBuilder(template.content)
            } else {
              $("#template-content").val(template.content)
            }

            kotacomAI.utils.showNotice("Template loaded successfully!", "success")
            this.updatePreview()
          } else {
            kotacomAI.utils.showNotice("Failed to load template: " + response.data.message, "error")
          }
        },
        error: () => {
          kotacomAI.utils.showNotice("Error loading template.", "error")
        },
      })
    }

    /**
     * Duplicate template
     */
    duplicateTemplate() {
      const templateId = new URLSearchParams(window.location.search).get("template_id")
      if (!templateId) {
        kotacomAI.utils.showNotice("Please load a template first to duplicate.", "warning")
        return
      }

      if (!confirm("Are you sure you want to duplicate this template?")) {
        return
      }

      $.ajax({
        url: kotacomAI.ajaxurl,
        type: "POST",
        data: {
          action: "kotacom_duplicate_template",
          nonce: kotacomAI.nonce,
          template_id: templateId,
        },
        success: (response) => {
          if (response.success) {
            kotacomAI.utils.showNotice("Template duplicated successfully!", "success")
            // Redirect to new template editor
            const newUrl = new URL(window.location.href)
            newUrl.searchParams.set("template_id", response.data.new_template_id)
            window.location.href = newUrl.href
          } else {
            kotacomAI.utils.showNotice("Failed to duplicate template: " + response.data.message, "error")
          }
        },
        error: () => {
          kotacomAI.utils.showNotice("Error duplicating template.", "error")
        },
      })
    }

    /**
     * Parse shortcode content back into visual builder components
     */
    parseContentToVisualBuilder(content) {
      $("#template-builder").empty() // Clear existing components

      // Simple regex to find top-level shortcodes and static content
      const regex =
        /(\[ai_content[^\]]*\]|\[ai_section[^\]]*\][\s\S]*?\[\/ai_section\]|\[ai_list[^\]]*\]|\[ai_conditional[^\]]*\][\s\S]*?\[\/ai_conditional\]|<hr[^>]*>|[^[<]+)/gi
      let match
      let lastIndex = 0

      while ((match = regex.exec(content)) !== null) {
        const fullMatch = match[0]
        const startIndex = match.index

        // Handle static content before the current match
        if (startIndex > lastIndex) {
          const staticContent = content.substring(lastIndex, startIndex).trim()
          if (staticContent) {
            this.addComponent("static-content", { content: staticContent })
          }
        }

        // Handle the matched shortcode or separator
        if (fullMatch.startsWith("[ai_content")) {
          const settings = this.parseShortcodeAttributes(fullMatch)
          this.addComponent("ai-content", settings)
        } else if (fullMatch.startsWith("[ai_section")) {
          const sectionContentMatch = fullMatch.match(/\[ai_section[^\]]*\]([\s\S]*?)\[\/ai_section\]/i)
          const innerContent = sectionContentMatch ? sectionContentMatch[1].trim() : ""
          const settings = this.parseShortcodeAttributes(fullMatch)
          settings.content = innerContent // Store inner content
          this.addComponent("ai-section", settings)
        } else if (fullMatch.startsWith("[ai_list")) {
          const settings = this.parseShortcodeAttributes(fullMatch)
          this.addComponent("ai-list", settings)
        } else if (fullMatch.startsWith("[ai_conditional")) {
          const conditionalContentMatch = fullMatch.match(/\[ai_conditional[^\]]*\]([\s\S]*?)\[\/ai_conditional\]/i)
          const innerContent = conditionalContentMatch ? conditionalContentMatch[1].trim() : ""
          const settings = this.parseShortcodeAttributes(fullMatch)
          settings.content = innerContent // Store inner content
          this.addComponent("conditional", settings)
        } else if (fullMatch.startsWith("<hr")) {
          this.addComponent("separator")
        } else {
          // This case should ideally be caught by the static content handling above
          const staticContent = fullMatch.trim()
          if (staticContent) {
            this.addComponent("static-content", { content: staticContent })
          }
        }
        lastIndex = regex.lastIndex
      }

      // Handle any remaining static content after the last match
      if (lastIndex < content.length) {
        const staticContent = content.substring(lastIndex).trim()
        if (staticContent) {
          this.addComponent("static-content", { content: staticContent })
        }
      }

      if ($("#template-builder").is(":empty")) {
        $("#template-builder").append(this.getBuilderPlaceholder())
      }
      this.updateTemplateStructure() // Re-generate content from parsed components
    }

    /**
     * Helper to parse shortcode attributes into an object
     */
    parseShortcodeAttributes(shortcodeString) {
      const attrs = {}
      const attrRegex = /(\w+)="([^"]*)"/g
      let match
      while ((match = attrRegex.exec(shortcodeString)) !== null) {
        attrs[match[1]] = match[2]
      }
      return attrs
    }

    /**
     * Get builder placeholder HTML
     */
    getBuilderPlaceholder() {
      return `
                <div class="builder-placeholder">
                    <p>${kotacomAI.strings.dragComponents}</p>
                    <p class="description">${kotacomAI.strings.orClickButtons}</p>
                </div>
            `
    }

    /**
     * Get template settings
     */
    getTemplateSettings() {
      return {
        auto_generate: $("#auto-generate").is(":checked") ? "true" : "false",
        cache_duration: $("#cache-duration").val(),
        // fallback_mode: $("#fallback-mode").val(), // This field is not in the template editor
      }
    }

    /**
     * Get variables
     */
    getVariables() {
      const variables = {}

      $(".variable-row").each(function () {
        const name = $(this).find(".variable-name").val()
        const defaultValue = $(this).find(".variable-default").val()
        const description = $(this).find(".variable-description").val()

        if (name) {
          variables[name] = {
            default: defaultValue,
            description: description,
          }
        }
      })

      return variables
    }

    /**
     * Add variable row
     */
    addVariable(name = "", defaultValue = "", description = "") {
      const variableHtml = `
                <div class="variable-row">
                    <input type="text" class="variable-name" placeholder="Variable name" value="${name}">
                    <input type="text" class="variable-default" placeholder="Default value" value="${defaultValue}">
                    <input type="text" class="variable-description" placeholder="Description" value="${description}">
                    <button type="button" class="remove-variable button button-small button-danger">Remove</button>
                </div>
            `

      $("#variables-list").append(variableHtml)
    }

    /**
     * Switch template type
     */
    switchTemplateType(type) {
      $("#visual-builder").hide()
      $("#shortcode-editor").hide()
      $("#gutenberg-editor").hide()

      switch (type) {
        case "visual":
          $("#visual-builder").show()
          this.parseContentToVisualBuilder($("#template-content").val()) // Parse current shortcode to visual
          break
        case "shortcode":
          $("#shortcode-editor").show()
          // Content is already in #template-content, no need to parse
          break
        case "gutenberg":
          $("#gutenberg-editor").show()
          break
      }
    }

    /**
     * Refresh template list dropdown (after save/duplicate)
     */
    refreshTemplateList() {
      $.ajax({
        url: kotacomAI.ajaxurl,
        type: "POST",
        data: {
          action: "kotacom_get_templates", // Need a new AJAX action for this
          nonce: kotacomAI.nonce,
        },
        success: (response) => {
          if (response.success) {
            const $select = $("#load-template")
            $select.empty().append('<option value="">Load Existing Template</option>')
            $.each(response.data.templates, (index, template) => {
              $select.append(`<option value="${template.ID}">${template.post_title}</option>`)
            })
          }
        },
      })
    }
  }

  // Initialize template editor when document is ready
  $(document).ready(() => {
    if ($("#template-editor").length) {
      new TemplateEditor()
    }
  })
})(window.jQuery) // Use window.jQuery to ensure jQuery is declared
