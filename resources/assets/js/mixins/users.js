import {fetchError, Hub} from '../lib.js'

export default {
    data() {
        return {
            users: (window.initialUsers || [])
        }
    },
    created() {
        if(this.isAdmin){
            this.fetchUsers();
        }
    },
    computed: {
        routeUser() {
            return this.users.find(u => u.id === this.route.user) || {}
        },
    },
    methods: {
        fetchUsers(service_ID) {
            this.statusUpdate(null, {active: true});

            if (service_ID) {
                console.log('fetching users for ' + service_ID);
                return this.$http.get('/api/ui/services/' + service_ID + '/users')
                    .then(({data}) => {
                        this.$set(this.routeService, 'users', data);
                    })
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

            let translation;

            switch (role) {
                case 'admin':
                    translation = 'Admin';
                    break;
                case 'AppUser':
                    translation = 'Gebruiker';
                    break;
                case 'Member':
                    translation = 'Lid';
                    break;
                case 'Owner':
                    translation = 'Eigenaar';
                    break;
                default:
                    translation = role;
            }

            return translation;
        }
    },
    mounted() {

        //todo split createRole & patchRole
        Hub.$on('createRole', newRole => {
            this.statusUpdate(null, {active: true});

            if (!newRole.service_id) {
                newRole.service_id = this.routeService.id
            }
            if (!newRole.service_id && newRole.srv) {
                newRole.service_id = newRole.srv.id
            }
            newRole.role = newRole.role || 'Member';
            newRole.user_id = newRole.user_id || newRole.id;
            if (!newRole.user_id && !newRole.email) {
                // Cannot continue without at least one of these
                return console.error('createRole: email is missing')
            } else if (!newRole.user_id) {
                // Create the missing user based on user.email
                // After the creation, the role will be added too
                Hub.$emit('createUser', newRole);
                return
            }

            this.$http.post('/api/ui/roles', newRole)
                .then(() => {
                    this.fetchUsers();
                    this.fetchServices();
                    this.modalClose();
                })
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
            // todo don't fetch all users, you only need the one.
                .then(this.fetchUsers(this.route.service))
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
                this.fetchUsers();
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
