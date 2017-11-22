<template>
    <tr :class="{'warning':!u.verified}">
        <td v-if="this.$root.isAdmin">
            <a v-if="this.$root.isAdmin" :href="'#!user/'+u.id">{{ u.name }}</a>
        </td>
        <td v-else>
            {{ u.name }}
        </td>
        <td>
            {{ u.email }}
        </td>
        <td>
            <select title="Gebruikers beheren" aria-label="Gebruikers beheren"
                    :disabled="isSelf || isAdmin" @change="changeRole" v-model="u.role">
                <option value="Owner"> {{ $root.translateRole("Owner") }}</option>
                <option value="Member">{{ $root.translateRole("Member") }}</option>
            </select>
        </td>
        <td v-if="u.verified" class="text-success">&checkmark;</td>
        <td v-else class="text-warning">&cross;</td>
        <td class="td-btn text-right">
            <button :disabled="isSelf || isAdmin" @click="$parent.banUser(u)" class="btn btn-default btn-icon">
                <i class="glyphicon glyphicon-ban-circle"></i>
            </button>
        </td>
    </tr>
</template>

<script>
    import {Hub} from '../lib.js'

    export default {
        props: ['u'],
        computed: {
            isSelf() {
                return this.u.id === this.$root.user.id;
            },
            isAdmin() {
                return this.u.role === "Admin";
            }
        },
        methods: {
            changeRole() {
                Hub.$emit('patchRole', this.u)
            },
        }
    }
</script>
