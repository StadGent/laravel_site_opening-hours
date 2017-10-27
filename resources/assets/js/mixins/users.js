import {fetchError, Hub} from '../lib.js'
import {ADMIN, OWNER, MEMBER} from "../constants";

export default {
    data() {
        return {
            users: (window.initialUsers || [])
        }
    },
    created() {
        if (this.isAdmin) {
            this.fetchUsers();
        }
    },
    computed: {},
    methods: {
        fetchUsers(service_ID) {

            this.statusUpdate(null, {active: true});

            if (service_ID) {
                return this.$http.get('/api/ui/services/' + service_ID + '/users')
                    .then(({data}) => {
                        this.$set(this.routeService, 'users', data);
                    }, (error) => {
                        // because we send requests not knowing if the user is authorized,
                        // we must intercept this error.
                        // todo: don't send requests not knowing if...
                        if (error.status !== 401) {
                            throw error;
                        }
                    })
                    .then(this.statusReset)
                    .catch(fetchError)
            }
            // only admin can fetch all users
            else if (this.isAdmin) {
                return this.$http.get('/api/ui/users')
                    .then(({data}) => {
                        this.users = data || [];
                    })
                    .then(this.statusReset)
                    .catch(fetchError)
            }
        },
        translateRole(role) {
            switch (role) {
                case 'admin':
                    return ADMIN;
                case 'Member':
                    return MEMBER;
                case 'Owner':
                    return OWNER;
                default:
                    return role;
            }
        }
    },
    mounted() {

        // todo change this name
        // triggered in the 'invite user' modals.
        // backend will create or update the user.
        Hub.$on('createRole', newRole => {
            this.statusUpdate(null, {active: true});

            if (!newRole.service_id && newRole.srv) {
                newRole.service_id = newRole.srv.id
            }
            else {
                newRole.service_id = this.routeService.id
            }

            newRole.role = newRole.role || 'Member';
            newRole.user_id = newRole.user_id || newRole.id;

            // only admin can assign specific users
            // owner does not know if user exists

            if (!newRole.user_id && !newRole.email) {
                // Cannot continue without at least one of these
                this.statusUpdate(null, {message: 'createRole: email is missing'});
                this.modalResume();
                return;
            }

            // backend will create the user if not found
            this.$http.post('/api/ui/inviteuser', newRole)
                .then(({data}) => {

                    // add user to service users
                    this.routeService.users.push(data);

                    // update all users if Admin
                    if(this.isAdmin){
                        if(this.users.findIndex(u => u.id === data.id) > -1) {
                            this.$set(this.users, index, data);
                        }
                        else {
                            this.users.push(data);
                        }
                    }
                })
                .then(this.modalClose)
                .then(this.statusReset)
                .catch(fetchError)

        });
        Hub.$on('patchRole', user => {
            this.statusUpdate(null, {active: true});

            user.service_id = user.service_id || this.routeService.id;
            user.role = user.role || 'Member';
            user.user_id = user.user_id || user.id;

            if (!user.user_id) {
                this.statusUpdate({message: 'User ID is missing'});
                return;
            }

            this.$http.patch('/api/ui/roles', user)
                .then(({data}) => {
                    user.role = data.role;
                })
                .then(this.statusReset)
                .catch(fetchError)
        });
        Hub.$on('deleteRole', role => {
            this.statusUpdate(null, {active: true});

            if (!role.user_id || !role.service_id) {
                this.statusUpdate(null, {message: 'Toegang kon niet ontzegd worden.'});
                return;
            }
            if (!confirm('Toegang ontzeggen?')) {
                this.statusUpdate(null, {message: 'Delete role canceled'});
                return;
            }

            this.$http.delete('/api/ui/roles?service_id=' + role.service_id + '&user_id=' + role.user_id)
                .then((data) => {
                    console.log(inert(data));
                    // this.fetchServices();

                    this.
                    this.modalClose();
                }).catch(fetchError)
        });

        Hub.$on('deleteUser', user => {
            this.statusUpdate(null, {active: true});

            if (!user.id) {
                this.statusUpdate(null, {message: 'deleteRole: id is required'});
                return;
            }
            this.$http.delete('/api/ui/users/' + user.id).then(() => {
                this.fetchUsers();
                this.fetchServices();
                this.modalClose();
            }).catch(fetchError)
        })
    }
}
