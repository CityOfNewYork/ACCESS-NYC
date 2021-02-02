<template>
    <div class='profile' :class="'profile--' + profile.type" v-if="profile">
        <span class='profile__name' v-html="profile.file"></span>
        <abbr v-if="profile.type == 'block'" title='block'>::{{profile.name}}</abbr>
        <span class='profile__duration'>- {{ (profile.duration * 1000).toFixed(1) }}ms -</span> 
        <span class='profile__duration' :class="{'profile__duration--high': getDurationPercentageParent() > 33, 'profile__duration--medium': getDurationPercentageParent() > 20, 'profile__duration--low': getDurationPercentageParent() < 10}">{{getDurationPercentageParent()}}% of parent</span>
        <span class='profile__duration' :class="{'profile__duration--high': getDurationPercentageRoot() > 33, 'profile__duration--medium': getDurationPercentageRoot() > 20, 'profile__duration--low': getDurationPercentageRoot() < 10}">({{getDurationPercentageRoot()}}% of total)</span>
        <div class='profile__children' v-for="(childProfile, index) in profile.profiles" :key="index">
            <Profile :profile="childProfile" :parentProfile="profile" :rootProfile="rootProfile" />
        </div>
    </div>
</template>

<script>
export default {
  name: 'Profile',
  props: [
    'profile',
    'parentProfile',
    'rootProfile'
  ],
  methods: {
      getDurationPercentageParent(){
          return ((this.profile.duration / this.parentProfile.duration) * 100).toFixed(1);
      },
      getDurationPercentageRoot(){
          return ((this.profile.duration / this.rootProfile.duration) * 100).toFixed(1);
      }
  }
}
</script>

<style>
.profile{
    color: var(--text-color);
}

.profile__name a{
    color: var(--link-color);
}

.profile__duration{
    margin-left: 0.2em;
}

.profile__duration--medium{
    color: var(--warning-color);
    font-style: italic;
}

.profile__duration--high{
    color: var(--alert-color);
    font-weight: bold;
}

.profile__duration--low{
    color: var(--muted-color);
}

.profile__children{
    margin-left: 1em;
    border-left: 1px solid var(--text-color);
    padding-left: 1em;
    position: relative;
}

.profile__children::before{
    position: absolute;
    content: '';
    left: 0em;
    height: 1px;
    background-color: var(--text-color);
    width: 0.8em;
    display: block;
    top: 0.8rem;
}
</style>
