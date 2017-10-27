import {fetchError, Hub} from '../lib.js'

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
            else {
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
                    return 'Admin';
                case 'Member':
                    return 'Lid';
                case 'Owner':
                    return 'Eigenaar';
                default:
                    return role;
            }
        }
    },
    mounted() {

        //todo split createRole & patchRole
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
                .then((data) => {
                    console.log(inert(data))
                    // todo handle result
                    // update channel users
                    // update all users if Admin
                })
                .then(this.modalClose)
                .then(this.statusReset)

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
                .then(() => {
                    this.fetchServices();
                    this.modalClose();
                }).catch(fetchError)
        });
        Hub.$on('fetchUser', newRole => {
            this.statusUpdate(null, {active: true});

            if (!newRole.service_id) {
                newRole.service_id = this.routeService.id
            }
            newRole.role = newRole.role || 'Member';
            newRole.user_id = newRole.user_id || newRole.id;
            if (!newRole.user_id && !newRole.email) {
                // Cannot continue without at least one of these
                this.statusUpdate(null, {message: 'createRole: email is missing'});
                return;
            } else if (!newRole.user_id) {
                // Create the missing user based on user.email
                // After the creation, the role will be added too
                Hub.$emit('createUser', newRole);
                return
            }

            this.$http.post('/api/ui/roles', newRole).then(() => {
                this.fetchUsers();
                this.fetchServices();
                this.modalClose();
            }).catch(fetchError)
        });
        Hub.$on('createUser', newUser => {
            this.statusUpdate(null, {active: true});

            if (newUser.id) {
                this.statusUpdate(null, {message: 'createRole: this user probably already exists'});
                return;
            }
            if (!newUser.email) {
                this.statusUpdate(null, {message: 'createRole: email is missing'});
                return;
            }

            newUser.name = newUser.name || newUser.email;

            this.$http.post('/api/ui/users', newUser).then(({data}) => {
                Object.assign(newUser, data);
                if (newUser.role) {
                    Hub.$emit('createRole', newUser);
                } else {
                    this.fetchServices();
                }
                if (this.isAdmin) {
                    this.fetchUsers();
                }
                this.modalClose();
            }).catch(fetchError)
        });
//todo can we remove this?
        Hub.$on('inviteUser', user => {
            alert('Uitnodiging opnieuw verzenden? (werkt nog niet)')
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
