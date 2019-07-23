import Vue from 'vue'

import draggable from 'vuedraggable';

import Home from './components/Icons/Home';
import Jira from './components/Icons/Jira';
import Loader from './components/Icons/Loader';
import LoadingView from './components/LoadingView';
import Logout from './components/Icons/Logout';
import Swimlane from './components/Swimlane.vue';
import SwimlaneIssue from './components/SwimlaneIssue.vue';
import User from './components/Icons/User';

Vue.component('draggable', draggable);

Vue.component('icon-home', Home);
Vue.component('icon-jira', Jira);
Vue.component('icon-logout', Logout);
Vue.component('icon-user', User);
Vue.component('loader', Loader);
Vue.component('loading-view', LoadingView);
Vue.component('swimlane', Swimlane);
Vue.component('swimlane-issue', SwimlaneIssue);