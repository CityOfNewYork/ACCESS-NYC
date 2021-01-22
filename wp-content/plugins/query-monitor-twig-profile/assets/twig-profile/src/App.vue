<template>
  <div class='twig-profile' :class="'twig-profile--' + qm_dark_mode">
      <h3>{{ i18n.viewing_profile }} {{ profileName }}</h3>
      <Profile :profile="currentProfile" :parentProfile="currentProfile" :rootProfile="currentProfile" />
      <h3>{{ i18n.controls }}</h3>
      <div class='twig-profile__form-part'>
        <label for='qm-twig-profile-name'>{{ i18n.profile_name }}</label>
        <input id='qm-twig-profile-name' type='text' class='profileName' v-model="profileName" />
        <button v-if='baseProfile.saved' disabled>{{ i18n.saved }}</button>
        <button v-else @click="saveProfile">{{ i18n.save_current }}</button>
      </div>
      <div class='twig-profile__form-part'>
        <label for='qm-twig-profile-selector'>{{ i18n.select_profile }}</label>
        <select id='qm-twig-profile-selector' v-model="selectedProfile">
            <option value='-1'>{{ i18n.current_request }}</option>
            <option v-for='(savedProfile, index) in savedProfiles' :key='index' :value='index'>
              {{ savedProfile.profileName }}
            </option>
        </select>
        <button @click="viewProfile">{{ i18n.view }}</button>
        <button :disabled="selectedProfile === '-1'" @click='removeProfile'>{{ i18n.remove }}</button>
      </div>
      <div class='twig-profile__form-part'>
        <button @click="clearProfiles">{{ i18n.clear_all }}</button>
      </div>
  </div>
</template>

<script>
import Profile from './components/Profile.vue';

export default {
  name: 'App',
  props: [
    'profile',
    'qm_dark_mode'
  ],
  data: () => {
    return {
      i18n: window.qm_twig_profile_l10n.strings,
      currentProfile: null,
      savedProfiles: [],
      profileName: window.qm_twig_profile_l10n.strings.current_request,
      selectedProfile: '-1'
    }
  },
  methods: {
    saveProfile(){
      this.baseProfile.saved = true;
      this.currentProfile = this.baseProfile;
      this.currentProfile.profileName = this.profileName;
      this.savedProfiles.unshift(this.currentProfile);
      window.localStorage.setItem('qm-twig-profiles', JSON.stringify(this.savedProfiles))
    },
    removeProfile(){
      if(this.selectedProfile === '-1'){
        return;
      }
      this.savedProfiles.splice(this.selectedProfile, 1);
      window.localStorage.setItem('qm-twig-profiles', JSON.stringify(this.savedProfiles))
      this.resetProfile()
    },
    clearProfiles(){
      this.savedProfiles = [];
      window.localStorage.removeItem('qm-twig-profiles');
      this.resetProfile();
    },
    viewProfile(){
      if(this.selectedProfile === '-1'){
        this.resetProfile();
        return;
      }
      this.currentProfile = this.savedProfiles[this.selectedProfile];
      this.profileName = this.currentProfile.profileName;
    },
    resetProfile(){
      this.currentProfile = this.baseProfile;
      this.profileName = window.qm_twig_profile_l10n.strings.current_request;
      this.selectedProfile = '-1';
    }
  },
  components: {
    Profile
  },
  created() {
    this.baseProfile = JSON.parse( this.profile )
    this.currentProfile = this.baseProfile
    this.savedProfiles = JSON.parse(window.localStorage.getItem('qm-twig-profiles'))
    if(!this.savedProfiles){
        this.savedProfiles = [];
      }
  }
}
</script>

<style>
.twig-profile{
  font-size: 16px;
  line-height: 25px;
}

.twig-profile label, .twig-profile input, .twig-profile select{
  display: block;
}

.twig-profile__form-part{
  padding: 10px 0;
}

h3 + .twig-profile__form-part{
  padding: 0 0 10px;
}

.twig-profile--light{
  --text-color: #000;
  --link-color: blue;
  --alert-color: #a00;
  --warning-color: #b33000;
  --muted-color: #666;
}

.twig-profile--dark{
  --text-color: #fff;
  --link-color: cyan;
  --alert-color: #f88;
  --warning-color: #ff4;
  --muted-color: #bbb;
}
</style>
