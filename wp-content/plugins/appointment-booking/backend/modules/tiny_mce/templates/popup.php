<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<div id="ab-tinymce-popup" style="display: none">
    <form id="ab-shortcode-form">
        <table>
            <?php do_action( 'bookly_render_popup_head' ) ?>
            <tr>
                <td>
                    <label for="ab-select-category"><?php _e( 'Default value for category select', 'bookly' ) ?></label>
                </td>
                <td>
                    <select id="ab-select-category">
                        <option value=""><?php _e( 'Select category', 'bookly' ) ?></option>
                    </select>
                    <div><label><input type="checkbox" id="ab-hide-categories" /><?php _e( 'Hide this field', 'bookly' ) ?></label></div>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="ab-select-service"><?php _e( 'Default value for service select', 'bookly' ) ?></label>
                </td>
                <td>
                    <select id="ab-select-service">
                        <option value=""><?php _e( 'Select service', 'bookly' ) ?></option>
                    </select>
                    <div><label><input type="checkbox" id="ab-hide-services" /><?php _e( 'Hide this field', 'bookly' ) ?></label></div>
                    <i><?php _e( 'Please be aware that a value in this field is required in the frontend. If you choose to hide this field, please be sure to select a default value for it', 'bookly' ) ?></i>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="ab-select-employee"><?php _e( 'Default value for employee select', 'bookly' ) ?></label>
                </td>
                <td>
                    <select class="ab-select-mobile" id="ab-select-employee">
                        <option value=""><?php _e( 'Any', 'bookly' ) ?></option>
                    </select>
                    <div><label><input type="checkbox" id="ab-hide-employee" /><?php _e( 'Hide this field', 'bookly' ) ?></label></div>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="ab-hide-number-of-persons"><?php echo esc_html( get_option( 'ab_appearance_text_label_number_of_persons' ) ) ?></label>
                </td>
                <td>
                    <label><input type="checkbox" id="ab-hide-number-of-persons" checked /><?php _e( 'Hide this field', 'bookly' ) ?></label>
                </td>
            </tr>
            <?php do_action( 'bookly_render_popup_after_number_of_persons' ) ?>
            <tr>
                <td>
                    <label for="ab-hide-date"><?php _e( 'Date', 'bookly' ) ?></label>
                </td>
                <td>
                    <label><input type="checkbox" id="ab-hide-date" /><?php _e( 'Hide this block', 'bookly' ) ?></label>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="ab-hide-week_days"><?php _e( 'Week days', 'bookly' ) ?></label>
                </td>
                <td>
                    <label><input type="checkbox" id="ab-hide-week_days" /><?php _e( 'Hide this block', 'bookly' ) ?></label>
                </td>
            </tr>
            <tr>
                <td>
                    <label for="ab-hide-time_range"><?php _e( 'Time range', 'bookly' ) ?></label>
                </td>
                <td>
                    <label><input type="checkbox" id="ab-hide-time_range" /><?php _e( 'Hide this block', 'bookly' ) ?></label>
                </td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <input class="button button-primary" id="ab-insert-shortcode" type="submit" value="<?php _e( 'Insert', 'bookly' ) ?>" />
                </td>
            </tr>
        </table>
    </form>
</div>
<style type="text/css">
    #ab-shortcode-form { margin-top: 15px; }
    #ab-shortcode-form table { width: 100%; }
    #ab-shortcode-form table td select { width: 100%; margin-bottom: 5px; }
    .ab-media-icon {
        display: inline-block;
        width: 16px;
        height: 16px;
        vertical-align: text-top;
        margin: 0 2px;
        background: url("<?php echo plugins_url( 'resources/images/calendar.png', __DIR__ ) ?>") 0 0 no-repeat;
    }
    #TB_overlay { z-index: 100001 !important; }
    #TB_window { z-index: 100002 !important; }
</style>
<script type="text/javascript">
    jQuery(function ($) {
        var $select_category        = $('#ab-select-category'),
            $select_location        = $('#ab-select-location'),
            $select_service         = $('#ab-select-service'),
            $hide_number_of_persons = $('#ab-hide-number-of-persons'),
            $hide_quantity          = $('#ab-hide-quantity'),
            $select_employee        = $('#ab-select-employee'),
            $hide_locations         = $('#ab-hide-locations'),
            $hide_categories        = $('#ab-hide-categories'),
            $hide_services          = $('#ab-hide-services'),
            $hide_staff             = $('#ab-hide-employee'),
            $hide_date              = $('#ab-hide-date'),
            $hide_week_days         = $('#ab-hide-week_days'),
            $hide_time_range        = $('#ab-hide-time_range'),
            $add_button             = $('#add-bookly-form'),
            $insert                 = $('#ab-insert-shortcode'),
            abLocations             = <?php echo $locationsJson ?>,
            abCategories            = <?php echo $categoriesJson ?>,
            abStaff                 = <?php echo $staffJson ?>,
            abServices              = <?php echo $servicesJson ?>;

        $add_button.on('click', function () {
            window.parent.tb_show(<?php echo json_encode( __( 'Insert Appointment Booking Form', 'bookly' ) ) ?>, this.href);
            window.setTimeout(function(){
                $('#TB_window').css({
                    'overflow-x': 'auto',
                    'overflow-y': 'hidden'
                });
            },100);
        });

        var AB_Location = Backbone.Model.extend();
        var AB_Category = Backbone.Model.extend();
        var AB_Service  = Backbone.Model.extend();
        var AB_Employee =  Backbone.Model.extend();

        var AB_Locations = Backbone.Collection.extend({
            model: AB_Location
        });

        var AB_Categories = Backbone.Collection.extend({
            model: AB_Category
        });

        var AB_Services = Backbone.Collection.extend({
            model: AB_Service
        });

        var AB_Staff = Backbone.Collection.extend({
            model: AB_Employee
        });

        var AB_OptionView = Backbone.View.extend({
            tagName: "option",

            initialize: function(){
                _.bindAll(this, 'render');
            },
            render: function(){
                this.$el.attr('value', this.model.get('id')).html(this.model.get('name'));
                return this;
            }
        });

        var AB_SelectView = Backbone.View.extend({
            events: {
                "change": "changeSelected"
            },

            initialize: function() {
                _.bindAll(this, 'addOne', 'addAll');
                this.selectView = [];
                this.collection.bind('reset', this.addAll);
            },
            addOne: function(location) {
                var optionView = new AB_OptionView({ model: location });
                this.selectView.push(optionView);
                this.$el.append(optionView.render().el);
            },
            addAll: function() {
                _.each(this.selectView, function(optionView) { optionView.remove(); });
                this.selectView = [];

                this.collection.each(this.addOne);

                if (this.selectedId) {
                    this.$el.val(this.selectedId);
                }
            },
            changeSelected: function() {
                this.setSelectedId(this.$el.val());
            }
        });
        var AB_LocationsView = AB_SelectView.extend({
            setSelectedId: function(locationId) {
                this.selectedId = locationId;
                this.staffView.selectedId = null;
                this.categoriesView.selectedId = null;
                if (locationId) {
                    this.categoriesView.collection.reset();
                    this.servicesView.collection.reset();
                    this.staffView.collection.reset();
                    this.categoriesView.setLocationId(locationId);
                    this.servicesView.setLocationId(locationId);
                    this.staffView.setLocationId(locationId);
                } else {
                    this.setDefaultValues();
                    this.categoriesView.setDefaultValues();
                    this.servicesView.setDefaultValues();
                    this.staffView.setDefaultValues();
                }
            },
            setStaffId: function(staffId) {
                this.populate(abStaff[staffId].locations);
            },
            setSelectedLocation: function(staffId) {
                var _this = this;
                _.each(abStaff[staffId].locations, function(object) {
                    _this.selectedId = object.id;
                });
            },
            populate: function(locations) {
                var location;
                for (var location_id in locations) {
                    location = new AB_Location();
                    location.set({
                        id: location_id,
                        name: locations[location_id].name
                    });
                    this.collection.push(location);
                }
                this.addAll();
            },
            setDefaultValues: function () {
                var location;
                for (var location_id in abLocations) {
                    location = new AB_Location();
                    location.set({
                        id: location_id,
                        name: abLocations[location_id].name
                    });
                    this.collection.push(location);
                }
                this.addAll();
            }
        });
        var AB_CategoriesView = AB_SelectView.extend({
            setSelectedId: function(categoryId) {
                this.selectedId = categoryId;
                if (this.staffView.selectedId && !this.servicesView.selectedId) {
                    this.servicesView.collection.reset();
                    this.servicesView.setCategoryEmployeeIds(categoryId, this.staffView.selectedId);
                } else {
                    this.servicesView.selectedId = null;
                    this.staffView.selectedId = null;
                    this.servicesView.collection.reset();
                    this.staffView.collection.reset();

                    if (categoryId) {
                        this.servicesView.setCategoryId(categoryId);
                        this.staffView.setCategoryId(categoryId);
                    } else {
                        if(!this.locationsView.selectedId) {
                            this.servicesView.setDefaultValues();
                            this.staffView.setDefaultValues();
                        }
                        else {
                            this.servicesView.setLocationId(this.locationsView.selectedId);
                            this.staffView.setLocationId(this.locationsView.selectedId);
                            this.setLocationId(this.locationsView.selectedId);
                        }
                    }
                }
            },
            setLocationId: function(locationId) {
                this.populate(abLocations[locationId].categories);
            },
            setEmployeeId: function(employeeId) {
                this.populate(abStaff[employeeId].categories);
            },
            populate: function(categories) {
                var category;
                for (var category_id in categories) {
                    category = new AB_Service();
                    category.set({
                        id: category_id,
                        name: categories[category_id].name
                    });
                    this.collection.push(category);
                }
                this.addAll();
            },
            setDefaultValues: function () {
                var category;
                for (var category_id in abCategories) {
                    category = new AB_Category();
                    category.set({
                        id: category_id,
                        name: abCategories[category_id].name
                    });
                    this.collection.push(category);
                }
                this.addAll();
            }
        });

        var AB_ServicesView = AB_SelectView.extend({
            setSelectedId: function(serviceId) {
                this.selectedId = serviceId;
                this.staffView.selectedId = null;
                if (serviceId) {
                    if (!this.categoriesView.selectedId) {
                        this.categoriesView.selectedId = abServices[serviceId].category_id;
                        this.categoriesView.$el.val(this.categoriesView.selectedId);
                    }
                    this.staffView.collection.reset();
                    this.staffView.setServiceId(serviceId);
                } else if (this.categoriesView.selectedId) {
                    this.staffView.$el.val('');
                    this.staffView.setCategoryId(this.categoriesView.selectedId)
                }
            },
            setLocationId: function(locationId) {
                this.populate(abLocations[locationId].services);
            },
            setCategoryId: function(categoryId) {
                this.populate(abCategories[categoryId].services);
            },
            setEmployeeId: function(employeeId) {
                this.populate(abStaff[employeeId].services);
            },

            setCategoryEmployeeIds: function(categoryId, employeeId) {
                var service, collection = this.collection, employee = abStaff[employeeId];
                // It is possible that employeeId does not exist and remain only as short code argument
                if (employee) {
                    _.each(employee.services, function(srv, serviceId) {
                        if (Number(srv.category_id) == categoryId) {
                            service = new AB_Service();
                            service.set({
                                id: serviceId,
                                name: employee.services[serviceId].name
                            });
                            collection.push(service);
                        }
                    });
                    this.addAll();
                }
            },
            populate: function(services) {
                var service;
                for (var service_id in services) {
                    service = new AB_Service();
                    service.set({
                        id: service_id,
                        name: services[service_id].name
                    });
                    this.collection.push(service);
                }
                this.addAll();
            },
            setDefaultValues: function () {
                var service;
                for (var service_id in abServices) {
                    service = new AB_Service();
                    service.set({
                        id: service_id,
                        name: abServices[service_id].name
                    });
                    this.collection.push(service);
                }
                this.addAll();
            }
        });

        var AB_StaffView = AB_SelectView.extend({
            setSelectedId: function(employeeId) {
                this.selectedId = employeeId;
                if (employeeId) {
                    if (!this.categoriesView.selectedId && !this.servicesView.selectedId) {
                        this.categoriesView.collection.reset();
                        this.servicesView.collection.reset();
                        this.servicesView.setEmployeeId(employeeId);
                        this.categoriesView.setEmployeeId(employeeId);
                    } else if (!this.servicesView.selectedId) {
                        this.servicesView.collection.reset();
                        this.servicesView.setCategoryEmployeeIds(this.categoriesView.selectedId, employeeId);
                    }

                    this.locationsView.collection.reset();
                    if(!this.locationsView.selectedId) {
                        this.locationsView.setSelectedLocation(employeeId);
                    }

                    this.locationsView.setStaffId(employeeId);

                } else if (!this.categoriesView.selectedId && !this.servicesView.selectedId) {
                    this.locationsView.selectedId = null;
                    this.locationsView.collection.reset();
                    this.categoriesView.collection.reset();
                    this.servicesView.collection.reset();
                    this.locationsView.setDefaultValues();
                    this.categoriesView.setDefaultValues();
                    this.servicesView.setDefaultValues();
                } else if (this.categoriesView.selectedId && !this.servicesView.selectedId) {
                    this.categoriesView.collection.reset();
                    this.categoriesView.setDefaultValues();
                    this.categoriesView.$el.val(this.categoriesView.selectedId);
                    this.servicesView.setCategoryId(this.categoriesView.selectedId);
                }
            },
            setLocationId: function(location_id) {
                this.populate(abLocations[location_id].staff);
            },
            setServiceId: function(serviceId) {
                this.populate(abServices[serviceId].staff);
            },
            setCategoryId: function(categoryId) {
                this.populate(abCategories[categoryId].staff);
            },
            populate: function(staff) {
                var employee;
                for (var employee_id in staff) {
                    employee = new AB_Employee();
                    employee.set({
                        id: employee_id,
                        name: staff[employee_id].name
                    });
                    this.collection.push(employee);
                }
                this.addAll();
            },
            setDefaultValues: function () {
                var employee;
                for (var employee_id in abStaff) {
                    employee = new AB_Employee;
                    employee.set({
                        id: employee_id,
                        name: abStaff[employee_id].name
                    });
                    this.collection.push(employee);
                }
                this.addAll();
            }
        });


        var locationsView = new AB_LocationsView({el: $select_location, collection: new AB_Locations() });
        var categoriesView = new AB_CategoriesView({el: $select_category, collection: new AB_Categories() });
        var servicesView = new AB_ServicesView({el: $select_service, collection: new AB_Services() });
        var staffView = new AB_StaffView({el: $select_employee, collection: new AB_Staff() });

        locationsView.staffView = staffView;
        locationsView.categoriesView = categoriesView;
        locationsView.servicesView = servicesView;
        categoriesView.servicesView = servicesView;
        categoriesView.locationsView = locationsView;
        categoriesView.staffView = staffView;
        servicesView.staffView = staffView;
        servicesView.categoriesView = categoriesView;
        staffView.locationsView = locationsView;
        staffView.categoriesView = categoriesView;
        staffView.servicesView = servicesView;
        locationsView.setDefaultValues();
        categoriesView.setDefaultValues();
        servicesView.setDefaultValues();
        staffView.setDefaultValues();

        $insert.on('click', function (e) {
            e.preventDefault();

            var insert = '[bookly-form';
            var hide   = [];
            if ($select_location.val()) {
                insert += ' location_id="' + $select_location.val() + '"';
            }
            if ($select_category.val()) {
                insert += ' category_id="' + $select_category.val() + '"';
            }
            if ($hide_locations.is(':checked')) {
                hide.push('locations');
            }
            if ($hide_categories.is(':checked')) {
                hide.push('categories');
            }
            if ($select_service.val()) {
                insert += ' service_id="' + $select_service.val() + '"';
            }
            if ($hide_services.is(':checked')) {
                hide.push('services');
            }
            if ($select_employee.val()) {
                insert += ' staff_member_id="' + $select_employee.val() + '"';
            }
            if ($hide_number_of_persons.is(':not(:checked)')) {
                insert += ' show_number_of_persons="1"';
            }
            if ($hide_quantity.is(':checked')) {
                hide.push('quantity');
            }
            if ($hide_staff.is(':checked')) {
                hide.push('staff_members');
            }
            if ($hide_date.is(':checked')) {
                hide.push('date')
            }
            if ($hide_week_days.is(':checked')) {
                hide.push('week_days')
            }
            if ($hide_time_range.is(':checked')) {
                hide.push('time_range');
            }
            if( hide.length > 0 ){
                insert += ' hide="' + hide.join() + '"';
            }
            insert += ']';

            window.send_to_editor(insert);

            $select_location.val('');
            $select_category.val('');
            $select_service.val('');
            $select_employee.val('');
            $hide_locations.prop('checked', false);
            $hide_categories.prop('checked', false);
            $hide_services.prop('checked', false);
            $hide_staff.prop('checked', false);
            $hide_date.prop('checked', false);
            $hide_week_days.prop('checked', false);
            $hide_time_range.prop('checked', false);
            $hide_number_of_persons.prop('checked', true);

            window.parent.tb_remove();
            return false;
        });
    });
</script>